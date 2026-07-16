/* ---------------- Mobile nav ---------------- */
const nav = document.getElementById('nav');
document.getElementById('burgerBtn').addEventListener('click', ()=>{
  nav.classList.toggle('open');
});

/* ---------------- Contact form ---------------- */
document.getElementById('contactForm').addEventListener('submit', e=>{
  e.preventDefault();
  showToast('Message sent! We\'ll get back to you within 24 hours.');
  e.target.reset();
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
