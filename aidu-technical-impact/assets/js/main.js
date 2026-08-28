document.addEventListener('DOMContentLoaded',()=>{
 const toggle=document.querySelector('.menu-toggle'); const nav=document.querySelector('.main-nav');
 if(toggle&&nav) toggle.addEventListener('click',()=>nav.classList.toggle('open'));
 const splash=document.getElementById('introSplash'); const skip=document.getElementById('skipIntro');
 if(splash){
   const key='aidutech_intro_seen_v4';
   if(sessionStorage.getItem(key)==='1'){splash.remove();}
   else{
     const finish=()=>{sessionStorage.setItem(key,'1');splash.classList.add('hide');setTimeout(()=>splash.remove(),800)};
     if(skip) skip.addEventListener('click',finish);
     setTimeout(finish,14500);
   }
 }
 document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault()}));
});
