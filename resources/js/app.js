
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!window.confirm(form.dataset.confirm))e.preventDefault();}));
document.querySelector('#add-question')?.addEventListener('click',()=>{const area=document.querySelector('#questions');if(area.children.length>=10)return;const row=document.createElement('div');row.className='row question';row.innerHTML='<label class="grow">Question<input name="screening_questions[]" maxlength="500" required></label><button type="button" class="secondary" data-remove-question>Remove</button>';area.append(row);row.querySelector('input').focus();});
document.querySelector('#questions')?.addEventListener('click',e=>{if(e.target.matches('[data-remove-question]'))e.target.closest('.question').remove();});
