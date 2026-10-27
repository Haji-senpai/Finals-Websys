document.querySelectorAll('#side nav a').forEach(a=>{if(location.href.startsWith(a.href))a.classList.add('on')});
document.getElementById('burger')?.addEventListener('click',()=>document.getElementById('side').classList.toggle('open'));
document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault()}));
document.querySelectorAll('.filter').forEach(i=>i.addEventListener('input',()=>{const q=i.value.toLowerCase();i.closest('.card').querySelectorAll('tbody tr').forEach(r=>r.hidden=!r.textContent.toLowerCase().includes(q))}));
document.querySelectorAll('form').forEach(f=>{const h=f.elements.student_id;if(h&&h.type==='hidden')f.addEventListener('submit',e=>{const s=document.getElementById('pick');if(!s||!s.value){e.preventDefault();alert('Choose a student first.');return}h.value=s.value})});
