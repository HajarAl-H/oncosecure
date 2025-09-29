</div>
</main>

<footer class="footer mt-auto">
  <div class="container py-3">
    <div class="row">
      <div class="col-12 col-md-4 mb-3 mb-md-0 text-center text-md-start">
        <h5 class="h6">🎗️ OncoSecure</h5>
        <p class="small text-white-50 mb-2">Empowering women through breast cancer care.</p>
      </div>
    </div>
    
    <div class="border-top border-white-25 mt-3 pt-3 text-center">
      <p class="small text-white-50 mb-1">
        &copy; <?php echo date('Y'); ?> OncoSecure. All rights reserved.
      </p>
      <p class="small text-white-50 mb-0">
        <i class="fas fa-ribbon" style="color:#ff6b9d;"></i> Breast Cancer Awareness <i class="fas fa-ribbon" style="color:#ff6b9d;"></i>
      </p>
    </div>
  </div>
</footer>

<button id="backToTop" class="btn btn-primary position-fixed d-none" 
        style="bottom:15px; right:15px; width:45px; height:45px; border-radius:50%; z-index:1000; padding:0;">
  <i class="fas fa-chevron-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const btn = document.getElementById('backToTop');
window.onscroll = ()=> btn.className = window.scrollY > 100 ? 'btn btn-primary position-fixed' : 'btn btn-primary position-fixed d-none';
btn.onclick = ()=> window.scrollTo({top:0, behavior:'smooth'});

document.addEventListener('DOMContentLoaded', ()=>{
  document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    link.addEventListener('click', ()=>{
      const nav = document.querySelector('.navbar-collapse');
      if(window.innerWidth < 992 && nav.classList.contains('show')){
        bootstrap.Collapse.getInstance(nav)?.hide();
      }
    });
  });
  
  setTimeout(()=>{
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(a=>{
      a.style.transition='opacity 0.5s'; 
      a.style.opacity='0';
      setTimeout(()=>a.remove(), 500);
    });
  }, 5000);
});
</script>
</body>
</html>