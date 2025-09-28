</div> <!-- Close main container -->
</main>

<!-- Footer -->
<footer class="footer mt-auto bg-dark text-white-50">
  <div class="container py-3 text-center">
    <h5 class="mb-2">🎗️ OncoSecure</h5>
    <p class="mb-1">&copy; <?= date('Y') ?> OncoSecure. Supporting women’s health.</p>
    <p class="mb-0"><i class="fas fa-ribbon me-1" style="color:#ff6b9d;"></i>Breast Cancer Awareness Month<i class="fas fa-ribbon ms-1" style="color:#ff6b9d;"></i></p>
  </div>
</footer>

<!-- Back to Top -->
<button id="backToTop" class="btn btn-primary position-fixed" style="bottom:20px; right:20px; width:45px; height:45px; border-radius:50%; display:none;" aria-label="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const backToTop = document.getElementById('backToTop');
  window.addEventListener('scroll', ()=> backToTop.style.display = window.scrollY > 100 ? 'block' : 'none');
  backToTop.addEventListener('click', ()=> window.scrollTo({top:0, behavior:'smooth'}));

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('form').forEach(f=>{
      f.addEventListener('submit', e=>{
        let invalid=false;
        f.querySelectorAll('[required]').forEach(i=>{
          if(!i.value.trim()){i.classList.add('is-invalid'); invalid=true;} 
          else i.classList.remove('is-invalid');
        });
        if(invalid){
          e.preventDefault();
          if(!f.querySelector('.alert-danger')){
            let a=document.createElement('div');
            a.className='alert alert-danger';
            a.textContent='Please fill required fields.';
            f.prepend(a);
          }
        }
      });
      f.querySelectorAll('input,select,textarea').forEach(i=>i.addEventListener('input', ()=>i.classList.remove('is-invalid')));
    });
    setTimeout(()=>document.querySelectorAll('.alert:not(.alert-permanent)').forEach(a=>{a.style.transition='opacity 0.5s'; a.style.opacity='0'; setTimeout(()=>a.remove(),500);}),5000);
  });
</script>

<?php if (isset($page_scripts)) echo $page_scripts; ?>
<?php if (defined('GA_TRACKING_ID') && GA_TRACKING_ID): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA_TRACKING_ID ?>"></script>
<script>
  window.dataLayer=window.dataLayer||[];
  function gtag(){dataLayer.push(arguments);}
  gtag('js',new Date());
  gtag('config','<?= GA_TRACKING_ID ?>');
</script>
<?php endif; ?>
</body>
</html>
