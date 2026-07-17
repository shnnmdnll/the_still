/* ---------------- Mobile nav ---------------- */
const nav = document.getElementById('nav');
document.getElementById('burgerBtn').addEventListener('click', ()=>{
  nav.classList.toggle('open');
});

/* Nav link active/filled icon toggle (Discover stays active on this page) */
document.querySelectorAll('.nav a[href^="#"]').forEach(link=>{
  link.addEventListener('click', ()=>{
    document.querySelectorAll('.nav a').forEach(l=>l.classList.remove('active'));
    link.classList.add('active');
  });
});

/* ---------------- Host CTA buttons ---------------- */
document.querySelectorAll('.btn-start, .btn-apply').forEach(btn=>{
  btn.addEventListener('click', e=>{
    e.preventDefault();
    showToast('Thanks for your interest! Our host team will reach out soon.');
  });
});

/* ---------------- Toast ---------------- */
let toastTimer;
function showToast(msg){
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=> toast.classList.remove('show'), 2400);
}