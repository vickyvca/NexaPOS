function setActive(route){
  const links = document.querySelectorAll('.nav a');
  links.forEach(a=>{
    const href = a.getAttribute('href')||'';
    a.classList.toggle('active', href === '#' + route);
  });
}

async function loadView(name){
  const el = document.getElementById('content');
  try{
    const res = await fetch('views/' + name + '.html', {cache:'no-cache'});
    if(!res.ok){ el.innerHTML = `<div class="card"><h3>Halaman tidak ditemukan</h3></div>`; return; }
    const html = await res.text();
    el.innerHTML = html;
    executeScripts(el);
  }catch(e){
    el.innerHTML = `<div class="card"><h3>Gagal memuat</h3><p class="muted">${e.message}</p></div>`;
  }
}

function executeScripts(container){
  const scripts = container.querySelectorAll('script');
  scripts.forEach(s => {
    const n = document.createElement('script');
    if(s.src){ n.src = s.src; } else { n.text = s.textContent; }
    document.body.appendChild(n);
    s.remove();
  });
}

function route(){
  const name = (location.hash || '#dashboard').slice(1);
  setActive(name);
  loadView(name);
}

window.addEventListener('hashchange', route);
window.addEventListener('DOMContentLoaded', route);

