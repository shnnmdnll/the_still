<?php
require_once __DIR__ . '/ocr_config.php';

/**
 * I-compress/i-resize ang larawan kung masyadong malaki (higit sa 1.5MB),
 * para tanggapin ito ng libreng OCR.space plan. Gumagawa ng pansamantalang
 * kopya — hindi ginagalaw ang orihinal na na-save na ID.
 */
function compressImageForOCR($sourcePath, $maxBytes = 1000000) {
    $originalSize = filesize($sourcePath);
    if ($originalSize <= $maxBytes) {
        return $sourcePath; // maliit na, gamitin na lang ang orihinal
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return $sourcePath; // hindi ma-process, subukan na lang ang orihinal
    }

    [$width, $height, $type] = $imageInfo;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        default:
            return $sourcePath;
    }

    if (!$source) {
        return $sourcePath;
    }

    // I-resize papuntang max 1400px width kung mas malaki
    $maxWidth = 1400;
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int) (($height / $width) * $newWidth);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $tempPath = sys_get_temp_dir() . '/ocr_temp_' . uniqid() . '.jpg';

    // Subukan ang iba't ibang quality levels hanggang bumaba sa 1MB
    $quality = 75;
    do {
        imagejpeg($resized, $tempPath, $quality);
        $quality -= 15;
    } while (filesize($tempPath) > $maxBytes && $quality > 10);

    imagedestroy($source);
    imagedestroy($resized);

    return $tempPath;
}

/**
 * I-verify ang isang ID image gamit ang OCR.space API.
 * Kinukuha ang text sa larawan, at ico-compare sa expected name (galing sa account).
 *
 * @param string $imagePath — full server path papunta sa image file
 * @param string $expectedName — pangalan ng user mula sa database
 * @return array ['success' => bool, 'extracted_text' => string, 'match_percentage' => float, 'confidence_level' => string]
 */
function verifyIdWithOCR($imagePath, $expectedName) {
    if (!file_exists($imagePath)) {
        return [
            'success' => false,
            'extracted_text' => '',
            'match_percentage' => 0,
            'confidence_level' => 'error',
        ];
    }

    $ocrImagePath = compressImageForOCR($imagePath);
    $isTempFile = ($ocrImagePath !== $imagePath);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, OCR_SPACE_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . OCR_SPACE_API_KEY]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($ocrImagePath),
        'language' => 'eng',
        'OCREngine' => 2,
        'isOverlayRequired' => 'false',
        'scale' => 'true',
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // TEMPORARY DEBUG LOG — titignan natin ito sa PHP error log
    error_log('OCR DEBUG - HTTP Code: ' . $httpCode . ' | cURL Error: ' . $curlError . ' | Response: ' . substr($response, 0, 500));

    // I-delete ang pansamantalang compressed file kung ginawa natin
    if ($isTempFile && file_exists($ocrImagePath)) {
        unlink($ocrImagePath);
    }

    if ($curlError || !$response) {
        return [
            'success' => false,
            'extracted_text' => '',
            'match_percentage' => 0,
            'confidence_level' => 'error',
        ];
    }

    $result = json_decode($response, true);

    if (empty($result['ParsedResults'][0]['ParsedText'])) {
        return [
            'success' => false,
            'extracted_text' => '',
            'match_percentage' => 0,
            'confidence_level' => 'error',
        ];
    }

    $extractedText = $result['ParsedResults'][0]['ParsedText'];

    // I-normalize ang parehong strings (uppercase, letters at spaces lang) bago i-compare
    $normalize = function ($str) {
        $str = strtoupper($str);
        return preg_replace('/[^A-Z\s]/', '', $str);
    };

    $normalizedExtracted = $normalize($extractedText);
    $normalizedExpected = $normalize($expectedName);

    similar_text($normalizedExtracted, $normalizedExpected, $matchPercentage);

    // Alternatibong check: kung nakikita ba ang buong pangalan bilang substring
    $nameParts = explode(' ', trim($normalizedExpected));
    $partsFound = 0;
    foreach ($nameParts as $part) {
        if (strlen($part) >= 2 && strpos($normalizedExtracted, $part) !== false) {
            $partsFound++;
        }
    }
    $partMatchPercentage = count($nameParts) > 0 ? ($partsFound / count($nameParts)) * 100 : 0;

    // Gamitin ang mas mataas sa dalawang paraan ng pag-check
    $finalPercentage = max($matchPercentage, $partMatchPercentage);

    if ($finalPercentage >= 70) {
        $confidenceLevel = 'high';
    } elseif ($finalPercentage >= 40) {
        $confidenceLevel = 'low';
    } else {
        $confidenceLevel = 'no_match';
    }

    return [
        'success' => true,
        'extracted_text' => $extractedText,
        'match_percentage' => round($finalPercentage, 1),
        'confidence_level' => $confidenceLevel,
    ];
}