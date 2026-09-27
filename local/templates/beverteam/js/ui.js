/* BEVERTEAM UI — шапка, меню, футер, авторизация, данные. Прототип, без бэкенда. */
(function(){
const I = {
  cat:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
  search:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  user:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
  heart:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 21s-7-4.5-9-9a5 5 0 0 1 9-3 5 5 0 0 1 9 3c-2 4.5-9 9-9 9Z"/></svg>',
  cart:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h8.4a2 2 0 0 0 2-1.5L21 8H6.5"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>',
  burger:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
  /* + мессенджеры и служебные иконки (доработка: «добавить все возможные способы связи») */
  tg:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="M21.6 4.3 2.9 11.5c-.9.35-.86 1.66.06 1.95l4.7 1.47 1.8 5.5c.24.73 1.18.92 1.68.33l2.5-2.94 4.7 3.45c.62.45 1.5.11 1.66-.64l3.2-14.9c.17-.8-.62-1.47-1.6-1.42Z"/></svg>',
  wa:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.23.64-1.34 1.24-1.85 1.28-.5.05-.97.23-3.26-.68-2.74-1.08-4.47-3.9-4.6-4.08-.13-.18-1.1-1.46-1.1-2.78s.7-1.98.94-2.25c.25-.27.54-.34.72-.34h.52c.17 0 .4-.06.62.48.23.55.79 1.9.86 2.04.07.14.11.3.02.48-.09.18-.13.3-.27.46-.13.16-.28.36-.4.48-.13.13-.27.28-.12.55.16.27.7 1.15 1.5 1.86 1.03.92 1.9 1.2 2.17 1.34.27.14.43.11.59-.07.16-.18.68-.79.86-1.06.18-.27.36-.23.6-.14.25.09 1.58.75 1.85.88.27.14.45.2.52.32.07.11.07.66-.16 1.3Z"/></svg>',
  max:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.2" stroke="currentColor" stroke-width="1.7"/><path d="M8 15.8V8.2h1.5l2.5 4 2.5-4H16v7.6h-1.6v-4.6L12 14.6 9.6 11.2v4.6Z" fill="currentColor"/></svg>',
  vk:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="M12.8 16.9c-5 0-8.2-3.5-8.3-9.3h2.6c.1 4.3 2.1 6.1 3.6 6.5V7.6h2.5v3.8c1.5-.16 3-1.9 3.5-3.8h2.4c-.4 2.3-2 4-3.1 4.7 1.1.6 3 2.1 3.7 4.6h-2.7c-.6-1.8-2-3.2-3.8-3.4v3.4Z"/></svg>',
  compare:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>',
  share:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="m8.4 10.8 7.2-4.2M8.4 13.2l7.2 4.2"/></svg>',
  arrL:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>',
  arrR:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6l6 6-6 6"/></svg>',
  /* иконки УТП */
  uRent:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h5M8 11h3M8 15.5h8"/></svg>',
  uPrice:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5Z"/><path d="M12 11.5v8.5M4 8.5l8 3 8-3"/></svg>',
  uSwap:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h13l-3-3M20 15H7l3 3"/></svg>',
  uTruck:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17.5" cy="18.5" r="1.6"/></svg>',
  uTool:'<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M15.5 3.5a5 5 0 0 0-6.2 6.2L3.6 15.4a2 2 0 1 0 2.8 2.8l5.7-5.7a5 5 0 0 0 6.2-6.2l-2.9 2.9-2.4-.6-.6-2.4Z"/></svg>'
};
I.box='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5Z"/><path d="M12 11.5v8.5M4 8.5l8 3 8-3"/></svg>';
I.truck='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17.5" cy="18.5" r="1.6"/></svg>';
I.home='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11 12 4l8 7v9H4z"/><path d="M10 20v-6h4v6"/></svg>';
/* ---------- Журнал: новости и статьи в одном инфоблоке, рубрика решает раздел ---------- */
// материалы журнала отдаёт сервер (header.php → bt_posts)
window.BT_POSTS=window.BT_POSTS||[];
window.BT_postUrl = p => p.url||'/blog/'+p.id+'/';

/* Фирменная заглушка вместо фото: SVG в data-URI, не зависит от внешних картинок.
   Используется, пока клиент не пришлёт реальные снимки. */
const PH_THEME={
  'Приготовление':['#0E0E0C','#D7E85C'], 'Уход':['#EFEFE9','#0E0E0C'], 'Обжарка':['#3E2C22','#F3EDE7'],
  'Для бизнеса':['#D7E85C','#0E0E0C'], 'Оборудование':['#1B1B18','#D7E85C'], 'Компания':['#E6E6DE','#0E0E0C'],
  'Сервис':['#0E0E0C','#EFEFE9']
};
window.BT_ph = (label, title, w, h) => {
  const [bg,fg]=PH_THEME[label]||['#EFEFE9','#0E0E0C'];
  w=w||520; h=h||325;
  const esc=t=>String(t).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const words=String(title||'').split(' '); const lines=[]; let cur='';
  words.forEach(word=>{ if((cur+' '+word).trim().length>18){lines.push(cur.trim());cur=word;} else cur+=' '+word; });
  if(cur.trim())lines.push(cur.trim());
  const L=lines.slice(0,3), top=h-72-(L.length-1)*28;
  const body=L.map((l,i)=>`<text x="40" y="${top+i*28}" font-family="Manrope,sans-serif" font-size="20" font-weight="700" fill="${fg}" opacity=".92">${esc(l)}</text>`).join('');
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
    <rect width="${w}" height="${h}" fill="${bg}"/>
    <g opacity=".13" fill="none" stroke="${fg}" stroke-width="1.4">
      <circle cx="${w-90}" cy="72" r="54"/><circle cx="${w-90}" cy="72" r="34"/><path d="M${w-150} 150h120M${w-90} 18v108"/>
    </g>
    <text x="40" y="58" font-family="'JetBrains Mono',monospace" font-size="12" letter-spacing="2" fill="${fg}" opacity=".65">${esc(String(label||'').toUpperCase())}</text>
    ${body}
    <text x="40" y="${h-26}" font-family="'JetBrains Mono',monospace" font-size="11" letter-spacing="1.5" fill="${fg}" opacity=".45">BEVERTEAM · ФОТО В РАБОТЕ</text>
  </svg>`;
  return 'data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg.replace(/\s+/g,' '));
};
/* глобальный фолбэк: если внешняя картинка не загрузилась, ставим заглушку */
window.BT_imgFallback = (img,label,title) => { img.onerror=null;
  /* заглушку рисуем под фактический размер блока, иначе текст обрезается при object-fit:cover */
  const r=img.getBoundingClientRect();
  img.src=BT_ph(label,title,Math.max(240,Math.round(r.width)||img.width||520),Math.max(160,Math.round(r.height)||img.height||325));
  img.style.objectFit='cover'; };
/* перерисовываем заглушки под фактический размер блока (крупная карточка журнала шире обычной) */
window.BT_phFit = root => (root||document).querySelectorAll('img[data-ph]').forEach(img=>{
  const r=img.getBoundingClientRect(); if(r.width<10) return;
  const w=Math.round(r.width), h=Math.round(r.height);
  if(img.dataset.phW==w+'x'+h) return; img.dataset.phW=w+'x'+h;
  img.src=BT_ph(img.dataset.ph,img.dataset.phT,w,h);
});
addEventListener('resize',()=>{clearTimeout(window.__phT);window.__phT=setTimeout(()=>BT_phFit(),200);});
window.BT_postDate = iso => { const d=new Date(iso), M=['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
  return d.getDate()+' '+M[d.getMonth()]+' '+d.getFullYear(); };
window.BT_postCard = p => `<a class="ncard" href="${BT_postUrl(p)}">
    <img src="${p.img||BT_ph(p.cat,p.t)}" ${p.img?'':`data-ph="${p.cat}" data-ph-t="${String(p.t).replace(/"/g,'&quot;')}"`} alt="${p.t}" loading="lazy" width="520" height="325">
    <div class="ncard__b"><div class="ncard__m"><span class="tag">${p.cat}</span><time datetime="${p.d}">${BT_postDate(p.d)}</time>${p.kind==='news'&&p.cat!=='Новости'?'<span class="tag tag--n">Новость</span>':''}</div>
    <h3>${p.t}</h3><p>${p.lead}</p></div></a>`;

/* ---------- Ремонт: бренды под посадочные страницы ---------- */
window.BT_BRANDS=[
  {id:'jetinno',slug:'Jetinno',auth:1,models:'JL05, JL15 VIVA, JL32, JL33, JL36',note:'авторизованный сервис, оригинальные запчасти на складе'},
  {id:'saeco',slug:'Saeco',models:'Lirika, Aulika, Royal, Idea',note:'частая проблема — заварочный блок и помпа'},
  {id:'jura',slug:'Jura',models:'WE8, X8, E6, Giga',note:'нужен фирменный сервис-режим, работаем с ним'},
  {id:'nuova',slug:'Nuova Simonelli',models:'Appia, Aurelia, Musica',note:'рожковые для кафе: группы, бойлер, теплообменник'},
  {id:'wmf',slug:'WMF',models:'1100S, 1500S, 5000S',note:'молочные системы и автопромывка'},
  {id:'delonghi',slug:"De'Longhi",models:'Magnifica, Dinamica, Eletta',note:'бытовые автоматы, ремонт и чистка на дому'}
];

/* ---------- Подписка на кофе ---------- */
window.BT_SUB={
  volumes:[{kg:3,rent:'JL05 бесплатно',disc:0.18},{kg:6,rent:'JL15 VIVA бесплатно',disc:0.22},{kg:9,rent:'JL36 бесплатно',disc:0.26},{kg:20,rent:'любая модель + вторая точка',disc:0.28}],
  periods:[{id:'m1',t:'Раз в месяц',k:1},{id:'w2',t:'Раз в 2 недели',k:2},{id:'m2',t:'Раз в 2 месяца',k:0.5}]
};

/* ---------- Отзывы на товар (UGC с фото) ---------- */
window.BT_REVIEWS={
  oromia:[
    {a:'Наталья К.',r:5,d:'2026-08-14',t:'Берём в офис на 20 человек второй год. Кислинка мягкая, в молоке не теряется. Раньше брали другой сорт, вернулись к этому.',ph:2,ok:1},
    {a:'Дмитрий',   r:5,d:'2026-07-02',t:'Заказал 5 кг, вышло заметно дешевле розницы. В зёрнах свежая дата обжарки, не залежалый.',ph:1,ok:1},
    {a:'Юлия М.',   r:4,d:'2026-06-19',t:'Вкус отличный, но для автомата пришлось подкрутить помол мельче обычного. В инструкции бы это написать.',ph:0,ok:1}
  ]
};
window.BT_rating = id => { const R=BT_REVIEWS[id]||[]; if(!R.length) return null;
  return {n:R.length, avg:Math.round(R.reduce((a,x)=>a+x.r,0)/R.length*10)/10}; };
window.BT_stars = (n,size) => `<span class="stars" style="${size?'font-size:'+size+'px':''}" aria-label="Оценка ${n} из 5">${'★'.repeat(Math.round(n))}${'☆'.repeat(5-Math.round(n))}</span>`;
I.phone='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h4l1.5 4-2.4 1.6a12 12 0 0 0 5.8 5.8L17 12l4 1.5v4a2 2 0 0 1-2.2 2C10.4 18.8 5.2 13.6 4.5 5.2A2 2 0 0 1 6.5 3Z"/></svg>';
I.mail='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/></svg>';

/* Текущий покупатель — с сервера (header.php → BT_USER), вход и выход — local/ajax/auth.php */
let USER=window.BT_USER||null;
try{ localStorage.removeItem('bt_user'); }catch(e){}
window.BT_user = () => USER;
window.BT_authPost = data => { const fd=new FormData(); Object.entries(data).forEach(([k,v])=>fd.append(k,v));
  fd.append('sessid',window.BT_SID||'');
  return fetch('/local/ajax/auth.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()); };
window.BT_login = u => { USER=u; window.BT_USER=u; document.dispatchEvent(new CustomEvent('bt:auth',{detail:u})); BT_authUpdate(); };
/* выход: из кабинета — на главную, с остальных страниц — та же страница уже гостем */
window.BT_logout = () => BT_authPost({action:'logout'}).then(()=>{ if(document.querySelector('.accp')) location.href='/'; else location.reload(); });
/* вход через сервисы (header.php → BT_IDP): только настроенные в модуле «Социальные сервисы», переход сразу — правило 11 */
const IDP=window.BT_IDP||[];
/* фирменные знаки и подписи кнопок по правилам сервисов; у кого знака нет — монограмма */
const IDP_LOGO={
  'i-ya':'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#FC3F1D"/><path fill="#fff" d="M13.32 7.666h-.924c-1.694 0-2.585.858-2.585 2.123 0 1.43.616 2.1 1.881 2.959l1.045.704-3.003 4.487H7.49l2.695-4.014c-1.55-1.111-2.42-2.19-2.42-4.015 0-2.288 1.595-3.85 4.62-3.85h3.003v11.868H13.32V7.666z"/></svg>',
  'i-vk':'<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="7" fill="#0077FF"/><path fill="#fff" d="M12.78 17.3c-5.41 0-8.5-3.71-8.63-9.88h2.71c.09 4.53 2.09 6.45 3.67 6.84V7.42h2.55v3.91c1.56-.17 3.2-1.95 3.75-3.91h2.55c-.43 2.42-2.21 4.2-3.48 4.93 1.27.59 3.3 2.14 4.07 4.95h-2.81c-.6-1.88-2.1-3.33-4.08-3.53v3.53h-.3z"/></svg>'};
const IDP_TEXT={'i-ya':'Войти с Яндекс ID','i-vk':'Войти с VK ID'};
window.BT_idpHtml = label => { const back=encodeURIComponent(location.pathname+location.search.replace(/[?&]auth_service_(id|error)=[^&]*/g,'').replace(/^&/,'?'));
  const href=p=>`/local/ajax/oauth.php?go=${p.id}&amp;back=${back}`;
  /* в окне входа — широкие фирменные кнопки, в строке на оформлении заказа — значки */
  if(!label) return `<div class="idp idp--big">${IDP.map(p=>
    `<a class="${p.cls}" href="${href(p)}" rel="nofollow">${IDP_LOGO[p.cls]||`<i>${p.m}</i>`}<span>${IDP_TEXT[p.cls]||'Войти через '+p.t}</span></a>`).join('')}</div>`;
  return `<div class="idp--row"><b>${label}</b><div class="idp">${IDP.map(p=>
  `<a class="${p.cls}" href="${href(p)}" rel="nofollow" title="${p.t}" aria-label="Войти через ${p.t}">${IDP_LOGO[p.cls]||p.m}</a>`).join('')}</div></div>`; };
window.BT_authUpdate = () => {
  const a=document.querySelector('.hact[href="/personal/"]'); if(!a) return;
  const l=a.querySelector('span:not(.cnt)');
  if(USER){ a.classList.add('is-auth'); if(l) l.textContent=(USER.name||'').split(' ')[0]||'Кабинет'; a.title=USER.email||''; }
  else { a.classList.remove('is-auth'); if(l) l.textContent='Кабинет'; a.removeAttribute('title'); }
};
I.repeat='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 13.7-5.6L20 8"/><path d="M20 4v4h-4"/><path d="M20 12a8 8 0 0 1-13.7 5.6L4 16"/><path d="M4 20v-4h4"/></svg>';
I.calendar='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>';
I.doc='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>';
I.close='<svg class="x" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>';
I.camera='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L8 6.5H4.8A1.8 1.8 0 0 0 3 8.3v9.9A1.8 1.8 0 0 0 4.8 20h14.4a1.8 1.8 0 0 0 1.8-1.8V8.3a1.8 1.8 0 0 0-1.8-1.8H16z"/><circle cx="12" cy="13" r="3.2"/></svg>';
I.trash='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 7V5h4v2M6 7l1 13h10l1-13M10 11v6M14 11v6"/></svg>';
I.star='<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor"><path d="m12 3 2.6 5.6 6 .8-4.4 4.2 1.1 6L12 16.8 6.7 19.6l1.1-6L3.4 9.4l6-.8Z"/></svg>';
window.BT_ICONS = I;

/* Реквизиты и контакты — единый источник для шапки, футера, контактов и микроразметки */
const CO = window.BT_CO = {
  name:'BEVERTEAM', legal:'ИП Чичиланов Василий Павлович',
  tel1:'+79955419399', tel1f:'+7 995 541-93-99',
  tel2:'+79043841388', tel2f:'+7 904 384-13-88',
  mail:'beteam@inbox.ru',
  zip:'620105', city:'Екатеринбург', street:'ул. Колокольная, 31А',
  hours:'Пн–Пт 10:00–17:00', hoursSvc:'Выезд инженера: Пн–Пт 9:00–17:00',
  ogrnip:'310667128700035', inn:'667113850366',
  msg:[['tg','Telegram','#'],['wa','WhatsApp','#'],['max','MAX','#'],['vk','ВКонтакте','#']]
};
const msgrHtml = (cls) => `<div class="msgr ${cls||''}">${CO.msg.map(m=>`<a href="${m[2]}" title="${m[1]}" aria-label="${m[1]}" rel="nofollow noopener" target="_blank">${I[m[0]]}</a>`).join('')}</div>`;
window.BT_msgr = msgrHtml;

/* УТП — единый блок для главной и категорий («добавить блок с УТП, 3–5 отличий») */
// карточки УТП отдаёт сервер (ИБ «Почему BEVERTEAM: карточки»): [svg-иконка, заголовок, текст]
window.BT_UTP = window.BT_UTP || [];
window.BT_utp = function(n){
  return BT_UTP.slice(0,n||4).map(u=>`<div class="utp__i"><div class="ic">${String(u[0]).startsWith('<')?u[0]:(I[u[0]]||'')}</div><b>${u[1]}</b><p>${u[2]}</p></div>`).join('');
};

const T = 'https://beverteam.ru/thumb/2/';
const IMG = {
  jl05:T+'vLwoMEqLBfRm8uQDZkEtzw/500r500/d/white_664320.png',
  jl15:T+'COfDzISOZlTwiag5PLlonw/500r500/d/jl15-front_600527.png',
  jl36:T+'2rZQ0hauuH1Zhlm55hGjmg/500r500/d/jl36-front-drk_772399.png',
  jl32:T+'u4-kN53dKi991RtghR0ozQ/500r500/d/jl32-34_962895.png',
  oromia:T+'-Di6hxUva-hkSPceqV5gjQ/400r400/d/espresso_1_kg_efiopiya_oromiya.png',
  oromiaL:T+'pVAxpqAwpy4o8Ykrph3RBQ/740r740/d/espresso_1_kg_efiopiya_oromiya.png',
  brazil:T+'0Nv7Qe9ZyszPmCUlbNnI-g/400r400/d/braziliya_sul.jpg',
  milk:T+'z_QSV_6QWtiUsw9DZO-pIg/400r400/d/espresso_1_kg_milk.png',
  smes:T+'lYlWqqAus27PG84BOvOAQw/400r400/d/smes.jpg',
  vending:T+'TueY_B0ztTeugmFybgqqlA/400r400/d/vending.jpg',
  earl:T+'AwTVVwbmV9tfeYXvyubwEw/400r400/d/erl_grej_3.jpg',
  assam:T+'4ewBxt6AE5QlWcGkU5ASpw/400r400/d/dsc_3801.jpg',
  chabrec:T+'nEhdF7oqTq3jF01yjq_fPg/400r400/d/dsc_3710.jpg',
  taiga:T+'uIa6JYRJRJwx-XDltRXg9w/400r400/d/dsc_3675.jpg',
  ivan:T+'DsGHko8k9KVCIBIr0NpTjg/400r400/d/ivan_chaj_2.jpg',
  masala:T+'Fw0xwY3F-iKxPhwulvj_ig/400r400/d/dsc_3702.jpg',
  catTea:T+'7lznU7X3oGrQ6QqJkHCWwQ/400r400/d/chaj_foto_2.jpg',
  catCoffee:T+'SH9xlwcMYZfVsxqkvmEjjA/400r400/d/photo-output_1.jpg',
  catMach:T+'kIizMCsiLk_FccU_BBFlKQ/400r400/d/photo-output.jpg',
  catAcc:T+'28S9b-n8LumkYpFbw5S65Q/400r400/d/whatsapp_image_2025-07-18_at_100716.jpg',
  catFilter:T+'8OBVMVgP-yR9ueonNFn2-g/400r400/d/krasnaya.jpg',
  hero:T+'FxOuyof2a5B1Nf2AfP4Cxw/1500r1500/d/img_8212_resized.jpg',
  drawing:T+'hz_SChSevwFHW5D8YNei9A/1500r1500/d/jl30waixingchicuntu1_1_page-0001.jpg'
};
window.BT_IMG = IMG;

/* товары — реальные данные с beverteam.ru */
// товары отдаёт сервер (header.php → bt_catalog_data)
window.BT_PRODUCTS = window.BT_DATA || {coffee:[],tea:[],machines:[],acc:[],rent:[]};

const fmt = n => n.toLocaleString('ru-RU')+' ₽';
window.BT_fmt = fmt;

/* ---------- корзина: состояние в localStorage, общее для всех страниц прототипа ---------- */
const ALL = () => Object.values(BT_PRODUCTS).flat();
window.BT_find = id => ALL().find(x=>x.id===id);
let CART;
// корзина Битрикса — источник истины (header.php → BT_BASKET), localStorage — только запасной вариант
if(window.BT_BASKET) CART = Object.assign({}, window.BT_BASKET.items);
else { try{ CART = JSON.parse(localStorage.getItem('bt_cart')||'null'); }catch(e){ CART=null; } }
if(!CART) CART = {};
window.BT_CART = CART;
const cartSave = () => { try{ localStorage.setItem('bt_cart',JSON.stringify(CART)); }catch(e){} };

/* ---------- сравнение: список id в localStorage, общий для всех страниц ---------- */
let CMP; try{ CMP=JSON.parse(localStorage.getItem('bt_cmp')||'null'); }catch(e){ CMP=null; }
if(!Array.isArray(CMP)) CMP=[];
window.BT_CMP = CMP;
const cmpSave = () => { try{ localStorage.setItem('bt_cmp',JSON.stringify(CMP)); }catch(e){} };
window.BT_cmpHas = id => CMP.indexOf(id)>=0;
window.BT_cmpToggle = id => { const i=CMP.indexOf(id); if(i<0) CMP.push(id); else CMP.splice(i,1);
  cmpSave(); BT_cmpUpdate(); document.dispatchEvent(new CustomEvent('bt:cmp',{detail:{id,on:i<0}})); return i<0; };
window.BT_cmpRemove = id => { const i=CMP.indexOf(id); if(i>=0){ CMP.splice(i,1); cmpSave(); BT_cmpUpdate();
  document.dispatchEvent(new CustomEvent('bt:cmp',{detail:{id,on:false}})); } };
window.BT_cmpClear = ids => { (ids||CMP.slice()).forEach(id=>{const i=CMP.indexOf(id);if(i>=0)CMP.splice(i,1);});
  cmpSave(); BT_cmpUpdate(); document.dispatchEvent(new CustomEvent('bt:cmp',{detail:{}})); };
/* ---------- избранное: список id в localStorage, общий для всех страниц ---------- */
let FAV; try{ FAV=JSON.parse(localStorage.getItem('bt_fav')||'null'); }catch(e){ FAV=null; }
if(!Array.isArray(FAV)) FAV=[];
window.BT_FAV = FAV;
window.BT_favHas = id => FAV.indexOf(String(id))>=0;
window.BT_favToggle = id => { id=String(id); const i=FAV.indexOf(id); if(i<0) FAV.push(id); else FAV.splice(i,1);
  try{ localStorage.setItem('bt_fav',JSON.stringify(FAV)); }catch(e){} BT_favUpdate(); return i<0; };
window.BT_favUpdate = () => {
  document.querySelectorAll('.pc__fav').forEach(b=>{ const pc=b.closest('[data-pc]');
    if(pc) b.setAttribute('aria-pressed', BT_favHas(pc.dataset.pc)?'true':'false'); });
  const a=document.querySelector('.hact[href="/personal/favorites/"]'); if(!a) return;
  let c=a.querySelector('.cnt'); if(!c){ c=document.createElement('span'); c.className='cnt'; a.insertBefore(c,a.lastElementChild); }
  c.textContent=FAV.length; c.hidden=!FAV.length;
};
window.BT_cmpUpdate = () => {
  document.querySelectorAll('.hact--cmp .cnt').forEach(c=>{ c.textContent=CMP.length; c.hidden=!CMP.length; });
  document.querySelectorAll('.pc__cmpi').forEach(b=>{ const pc=b.closest('[data-pc]');
    if(pc) b.setAttribute('aria-pressed', BT_cmpHas(pc.dataset.pc)?'true':'false'); });
};
window.BT_cartItems = () => Object.keys(CART).map(id=>{const p=BT_find(id);return p?{...p,q:CART[id]}:null;}).filter(Boolean);
window.BT_cartTotal = () => BT_cartItems().reduce((a,c)=>{const p=c.bulk?BT_tier(c,c.q).p:c.p;/* оптовая ступень, как в карточке */a.n+=c.q;a.sum+=p*c.q;a.disc+=c.old?(c.old-c.p)*c.q:0;return a;},{n:0,sum:0,disc:0});
window.BT_cartSet = (id,q) => { const was=CART[id]||0;
  if(q>0) CART[id]=q; else delete CART[id]; cartSave(); BT_cartUpdate(true); document.dispatchEvent(new CustomEvent('bt:cart',{detail:{id,q}}));
  BT_cartSync(id,q,was); };
/* запись в корзину Битрикса; не приняли — откатываем количество на экране */
window.BT_cartSync = (id,q,was) => {
  const fd=new FormData(); fd.append('action','set'); fd.append('id',id); fd.append('q',q);
  fd.append('sessid',window.BT_SID||'');
  return fetch('/local/ajax/cart.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(d=>{
    if(!d.ok) throw new Error(d.error||'cart');
    window.BT_BASKET={items:d.items,sum:d.sum};
  }).catch(()=>{
    if(was>0) CART[id]=was; else delete CART[id]; cartSave(); BT_cartUpdate(false);
    document.dispatchEvent(new CustomEvent('bt:cart',{detail:{id,q:was}}));
    BT_toast('Не получилось обновить корзину — попробуйте ещё раз');
  });
};
window.BT_cartUpdate = pulse => {
  const t=BT_cartTotal(), a=document.querySelector('.hact[href="/personal/cart/"]'); if(!a) return;
  const c=a.querySelector('.cnt'); if(c){ c.textContent=t.n; c.hidden=!t.n; }
  const l=a.querySelector('span:not(.cnt)'); if(l) l.textContent=t.n?fmt(t.sum):'Корзина';
  if(pulse){a.classList.remove('is-pulse');void a.offsetWidth;a.classList.add('is-pulse');}
};

/* ---------- даты: сборка сегодня (до 17:00 в будни), курьер по Екатеринбургу — следующий рабочий день ---------- */
const MONTHS=['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
const isWork = d => d.getDay()!==0 && d.getDay()!==6;
const nextWork = (d,n) => { const x=new Date(d); while(n>0){ x.setDate(x.getDate()+1); if(isWork(x)) n--; } return x; };
window.BT_fmtDate = (d,short) => d.getDate()+' '+(short?MONTHS[d.getMonth()].slice(0,3):MONTHS[d.getMonth()]);
window.BT_dates = (days) => {
  const now=new Date(); let pack=new Date(now);
  if(!isWork(now)||now.getHours()>=17) pack=nextWork(now,1);           /* после 17:00 и в выходные — первый рабочий день */
  const ship=nextWork(pack,1), deliver=days?nextWork(pack,days):ship;
  const rel = d => { const t=new Date();t.setHours(0,0,0,0); const x=new Date(d);x.setHours(0,0,0,0); const k=Math.round((x-t)/864e5); return k===0?'сегодня':k===1?'завтра':''; };
  return {pack,ship,deliver,relPack:rel(pack),relShip:rel(ship),relDeliver:rel(deliver)};
};
window.BT_timeline = () => { const d=BT_dates(); const f=(r,x)=>(r?r+', ':'')+BT_fmtDate(x);
  return `<div class="tl3">
    <div>${I.box}<span><small>Соберём</small><b>${f(d.relPack,d.pack)}</b></span></div>
    <div>${I.truck}<span><small>Передадим курьеру</small><b>${f(d.relShip,d.ship)}</b></span></div>
    <div>${I.home}<span><small>Екатеринбург / СДЭК по России</small><b>${f(d.relDeliver,d.deliver)}</b></span></div>
  </div>`; };
/* блок «до бесплатной доставки» с прогрессом */
window.BT_freeBar = sum => { const T=3000, p=Math.min(100,Math.round(sum/T*100));
  return sum>=T ? `Доставка по Екатеринбургу — бесплатно, по России — СДЭК<i style="--p:100%"></i>` : `До бесплатной доставки по Екатеринбургу не хватает <b>${fmt(T-sum)}</b><i style="--p:${p}%"></i>`; };

/* кнопка/степпер для карточки: в корзине — степпер с ценой на месте кнопки */
/* подвал карточки: цена + контрол. Обновляется точечно, без пересборки карточки */
/* Единица продажи. kg — кофе: цена за килограмм, действует оптовая сетка.
   pcs — чай и аксессуары: фасовка зашита в товар, считаем штуками. */
window.BT_unit = m => m.bulk ? 'kg' : 'pcs';
/* Цена за кг при заданном объёме: берём ближайшую ступень сетки сверху вниз */
window.BT_tier = (m,kg) => {
  if(!m.bulk) return {kg:1,p:m.p};
  let t=m.bulk[0]; m.bulk.forEach(x=>{ if(kg>=x.kg) t=x; }); return t;
};
window.BT_tierPct = (m,t) => m.bulk&&t.p<m.bulk[0].p ? Math.round((1-t.p/m.bulk[0].p)*100) : 0;
/* Ряд быстрого выбора объёма — торговые предложения по весу */
window.BT_packs = (m,big) => {
  if(!m.bulk) return '';
  const q=CART[m.id]||0;
  return `<div class="packs ${big?'packs--lg':''}" data-packs="${m.id}">
    ${big?'<span class="packs__lab">Объём заказа</span>':''}
    ${m.bulk.map(t=>{const pct=BT_tierPct(m,t);
      return `<button type="button" data-kg="${t.kg}" aria-pressed="${q===t.kg}">${t.kg} кг${pct?`<s>−${pct}%</s>`:''}</button>`;}).join('')}
  </div>`;
};

window.BT_footInner = m => {
  const q = m.rent ? 0 : (CART[m.id]||0);
  const n = q||1, t = BT_tier(m,n);
  const price = m.p ? fmt((m.bulk?t.p:m.p)*n) : 'По запросу';
  const sub = m.unit ? `<s>${m.unit}</s>`
    : m.bulk ? `<s>${fmt(t.p)} за кг${n>1?` × ${n} кг`:''}</s>`
    : (n>1 ? `<s>${fmt(m.p)} × ${n} шт</s>` : (m.pre ? '<s>предзаказ</s>' : ''));
  const old = m.old ? `<span class="price--old">${fmt(m.old*n)}</span>` : '';
  return `<div>${old}<span class="price">${price}${sub}</span></div>${BT_addCtl(m)}`;
};
window.BT_addCtl = m => {
  if(m.rent) return `<a class="btn btn--sm" href="/arenda-kofemashin/#calc">Арендовать</a>`;
  const q=CART[m.id]||0, price=m.p?fmt(m.p):'По запросу';
  if(!q) return `<button class="btn btn--sm" data-add="${m.id}">${m.pre?'Предзаказ':'В корзину'}</button>`;
  const u = BT_unit(m)==='kg' ? 'кг' : 'шт';
  return `<span class="addq" data-id="${m.id}" title="В корзине"><button data-q="-" aria-label="Уменьшить">−</button><b>${q}<i>${u}</i></b><button data-q="+" aria-label="Увеличить">+</button></span>`;
};

window.BT_card = function(m){
  /* скидки — отдельным бейджем на изображении (доработка по категориям) */
  const tiers = m.tiers ? `<div class="pc__tiers">${m.tiers.map(t=>`<span>${t}</span>`).join('')}</div>` : '';
  /* краткие характеристики вместо «простыни» текста */
  const scales = m.sc ? `<div class="pc__scales">${m.sc.map(x=>`<div class="pc__scale"><span>${x[0]}</span><i style="--v:${x[1]}%"></i></div>`).join('')}</div>` : '';
  /* количество теперь живёт в самой кнопке (степпер после добавления), в строке инструментов — только сравнение */
  const tools = '';
  return `<article class="pc" data-pc="${m.id}" itemscope itemtype="https://schema.org/Product">
    <meta itemprop="name" content="${m.n}"><meta itemprop="image" content="${m.img}"><meta itemprop="description" content="${m.par}">
    ${m.badges?`<div class="pc__badges">${m.badges.map(b=>`<span class="badge">${b}</span>`).join('')}</div>`:''}
    ${tiers}
    <div class="pc__acts">
      <button class="pc__fav" aria-pressed="false" title="В избранное" aria-label="В избранное">${I.heart}</button>
      ${m.rent?'':`<button class="pc__cmpi" aria-pressed="false" title="Сравнить" aria-label="Сравнить">${I.compare}</button>`}
    </div>
    <a class="pc__ph" href="${m.url}"><img src="${m.img}" alt="${m.n}" loading="lazy"></a>
    <h3><a href="${m.url}" itemprop="url">${m.n}</a></h3>
    <p class="pc__par">${m.par}</p>
    ${scales}
    ${BT_packs(m)}
    <span class="pc__stock${m.stock?'':' pc__stock--no'}">${m.stock?'В наличии':'Под заказ'}</span>
    ${tools}
    <div class="pc__foot" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
      <meta itemprop="priceCurrency" content="RUB">${m.p?`<meta itemprop="price" content="${m.p}">`:''}
      <link itemprop="availability" href="https://schema.org/${m.stock?'InStock':'PreOrder'}">
      ${BT_footInner(m)}</div>
  </article>`;
};

/* ---------- каталог для меню ---------- */
// разделы мегаменю отдаёт сервер (bt_mega_cats): {t, h, sub:[[название, ссылка]], promo:{img, b, t, h}}
const CATS = window.BT_CATS || [];

/* Структура горизонтального меню — по прототипу «Горизонтальное меню».
   Пункт «Главная» не выводим (дубль логотипа), «Аренда кофемашин» — отдельный пункт. */
const NAV = [
  {t:'О компании', h:'/o-kompanii/', sub:[['Отзывы о нас','/o-kompanii/#reviews'],['Написать нам','/kontakty/#form']]},
  {t:'Магазин', h:'/magazin/', sub:[['Кофе','/magazin/'],['Чай','/magazin/'],['Кофемашины','/magazin/'],['Аксессуары','/magazin/']]},
  {t:'Аренда кофемашин', h:'/arenda-kofemashin/', sub:[['Для офиса','/arenda-kofemashin/'],['Для кафе и HoReCa','/arenda-kofemashin/'],['На мероприятие','/arenda-kofemashin/'],['Кофе по подписке','/podpiska/']]},
  {t:'Сервис', h:'/servis/', sub:[['Ремонт кофемашин','/servis/remont-kofemashin/'],['Плановое ТО и чистка','/servis/#price'],['Продажа оборудования','/servis/'],['Вызвать инженера','/servis/remont-kofemashin/#form']]},
  {t:'Журнал', h:'/blog/', sub:[['Все материалы','/blog/']]},
  {t:'Ещё', h:'', sub:[['Оплата и доставка','/oplata-i-dostavka/'],['Возврат и обмен','/vozvrat-i-obmen/'],['О компании','/o-kompanii/'],['Контакты','/kontakty/'],['Карта сайта','/sitemap/']]}
];
function navHtml(){
  return NAV.map(n=>`<div class="nav-i${n.sub.length?'':' nav-i--plain'}">
    <a href="${n.h||'#'}"${n.h?'':' onclick="return false"'}>${n.t}</a>
    ${n.sub.length?`<div class="nav-d">${n.sub.map(x=>`<a href="${x[1]}">${x[0]}</a>`).join('')}</div>`:''}
  </div>`).join('');
}
function header(){
  const cartCnt = 2;
  return `<header class="hdr" id="hdr">
  <div class="wrap hdr__top">
    <button class="burger" id="burger" aria-label="Меню">${I.burger}</button>
    <a class="brand" href="/" title="Чай и кофе для дома и бизнеса BEVERTEAM" aria-label="Чай и кофе для дома и бизнеса BEVERTEAM — на главную"><span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span></a>
    <button class="catbtn" id="catbtn" aria-expanded="false" aria-controls="mega"><span class="catbtn__i">${I.cat}${I.close}</span><span class="lbl">Каталог</span></button>
    <div class="hdr__msgr">${msgrHtml()}</div>
    <div class="hdr__acts">
      <a class="hdr__tel" href="tel:${CO.tel1}">${CO.tel1f}</a>
      <button class="hact" id="srchBtn" aria-label="Поиск по сайту" aria-haspopup="dialog">${I.search}<span>Поиск</span></button>
      <a class="hact" href="/personal/">${I.user}<span>Кабинет</span></a>
      <a class="hact" href="#" onclick="BT_toast('Избранное — в прототипе не реализовано');return false">${I.heart}<span>Избранное</span></a>
      <a class="hact hact--cmp" href="/magazin/compare/" aria-label="Сравнение товаров">${I.compare}<span class="cnt" hidden>0</span><span>Сравнение</span></a>
      <a class="hact" href="/personal/cart/" aria-label="Корзина">${I.cart}<span class="cnt"${cartCnt?'':' hidden'}>${cartCnt}</span><span>Корзина</span></a>
    </div>
  </div>
  <div class="wrap hdr__nav">
    ${navHtml()}
    <span class="r"><b>${CO.city}</b>, ${CO.street} · ${CO.hours}</span>
  </div>
  <div class="mega" id="mega"><div class="wrap mega__in">
    <div class="mega__cats" id="megaCats">${CATS.map((c,i)=>`<button aria-selected="${i===1}" data-i="${i}">${c.t}</button>`).join('')}</div>
    <div class="mega__panel" id="megaPanel"></div>
  </div></div>
</header>`;
}
function megaPanel(i){
  const c = CATS[i]; const half = Math.ceil(c.sub.length/2);
  const li = s => `<li><a href="${s[1]}">${s[0]}</a></li>`;
  return `<h4><a href="${c.h}">${c.t} →</a></h4>
    <ul>${c.sub.slice(0,half).map(li).join('')}</ul>
    <ul>${c.sub.slice(half).map(li).join('')}</ul>
    ${c.promo?`<a class="mega__promo" href="${c.promo.h}"><img src="${c.promo.img}" alt="" loading="lazy"><b>${c.promo.b}</b><span class="muted" style="font-size:13px">${c.promo.t}</span></a>`:''}`;
}
function drawer(){
  return `<div class="drawer" id="drawer">
    <div class="drawer__bg" data-close></div>
    <div class="drawer__p">
      <div class="drawer__hd"><a class="brand" href="/"><span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span></a><button class="drawer__x" data-close aria-label="Закрыть">×</button></div>
      <div class="drawer__s"><button class="btn btn--ghost btn--block" id="srchBtnM" style="justify-content:flex-start;gap:12px">${I.search} Поиск по каталогу</button></div>
      ${CATS.map(c=>`<details class="acc"><summary>${c.t}</summary><ul>${c.sub.map(s=>`<li><a href="${s[1]}">${s[0]}</a></li>`).join('')}<li><a href="${c.h}" class="link">Все в разделе</a></li></ul></details>`).join('')}
      <a class="drawer__l" href="/podpiska/">Кофе по подписке</a>
      <a class="drawer__l" href="/servis/">Услуги и сервис</a>
      <a class="drawer__l" href="/servis/remont-kofemashin/">Ремонт кофемашин</a>
      <a class="drawer__l" href="/blog/">Журнал</a>
      <a class="drawer__l" href="/o-kompanii/">О компании</a>
      <a class="drawer__l" href="/oplata-i-dostavka/">Оплата и доставка</a>
      <a class="drawer__l" href="/kontakty/">Контакты</a>
      <a class="drawer__l" href="/personal/">Личный кабинет</a>
      <div class="drawer__ft">
        <a class="tel" href="tel:${CO.tel1}">${CO.tel1f}</a>
        <a class="tel" href="tel:${CO.tel2}" style="font-size:14px">${CO.tel2f}</a>
        <a href="mailto:${CO.mail}" style="font-size:13.5px;font-weight:600">${CO.mail}</a>
        <span class="muted" style="font-size:12.5px">${CO.zip}, ${CO.city}, ${CO.street} · ${CO.hours}</span>
        ${msgrHtml('msgr--lg')}
        <a class="btn btn--block" href="/kontakty/#form">Оставить заявку</a>
      </div>
    </div></div>`;
}
function footer(){
  /* Микроразметка LocalBusiness в футере — требование из «Списка доработок», п.1 */
  return `<footer class="ftr" itemscope itemtype="https://schema.org/LocalBusiness"><div class="wrap">
  <meta itemprop="name" content="${CO.name} — чай и кофе для дома и бизнеса">
  <meta itemprop="priceRange" content="650–297000 ₽">
  <div class="ftr__g">
    <div><a class="brand" href="/" style="margin-bottom:14px" title="Чай и кофе для дома и бизнеса BEVERTEAM"><span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span></a>
      <p style="margin:0;max-width:28ch">Чай, кофе и оборудование для дома и бизнеса. ${CO.city}, с 2010 года.</p>
      <div class="ftr__soc" style="margin-top:14px">${msgrHtml()}</div>
      <div class="ftr__hours"><b>Время работы</b>Офис: ${CO.hours}<br>${CO.hoursSvc}<br>Сб–Вс — выходные</div></div>
    <div><h5>Каталог</h5><ul><li><a href="/magazin/">Чай</a></li><li><a href="/magazin/">Кофе BOTANICA</a></li><li><a href="/magazin/">Автоматические кофемашины JETINNO</a></li><li><a href="/magazin/">Аксессуары</a></li><li><a href="/arenda-kofemashin/">Аренда кофемашин</a></li></ul></div>
    <div><h5>Услуги</h5><ul><li><a href="/podpiska/">Кофе по подписке</a></li><li><a href="/arenda-kofemashin/">Аренда кофемашин</a></li><li><a href="/arenda-kofemashin/#event">Аренда на мероприятия</a></li><li><a href="/servis/">Продажа оборудования</a></li><li><a href="/servis/remont-kofemashin/">Ремонт кофемашин</a></li><li><a href="/magazin/">Кофе оптом</a></li></ul></div>
    <div><h5>Покупателям</h5><ul><li><a href="/oplata-i-dostavka/">Оплата и доставка</a></li><li><a href="/vozvrat-i-obmen/">Возврат и обмен</a></li><li><a href="/politika-konfidencialnosti/">Политика обработки персональных данных</a></li><li><a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a></li><li><a href="/o-kompanii/">О компании</a></li><li><a href="/blog/">Журнал</a></li><li><a href="/podbor-kofe/">Подбор кофе</a></li><li><a href="/personal/">Личный кабинет</a></li><li><a href="/sitemap/">Карта сайта</a></li></ul></div>
    <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><h5>Контакты</h5><ul>
      <li><a href="tel:${CO.tel1}" itemprop="telephone">${CO.tel1f}</a></li>
      <li><a href="tel:${CO.tel2}">${CO.tel2f}</a></li>
      <li><a href="mailto:${CO.mail}" itemprop="email">${CO.mail}</a></li>
      <li><span itemprop="postalCode">${CO.zip}</span>, <span itemprop="addressLocality">${CO.city}</span>,<br><span itemprop="streetAddress">${CO.street}</span></li>
      <li><meta itemprop="openingHours" content="Mo-Fr 10:00-17:00"><span class="muted">${CO.hours}</span></li>
    </ul></div>
  </div>
  <div class="ftr__b"><span>© 2010–2026 ${CO.name} · ${CO.legal} · ОГРНИП ${CO.ogrnip} · ИНН ${CO.inn}</span><span><a href="/politika-konfidencialnosti/">Политика конфиденциальности</a> · <a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a> · <a href="/sitemap/">Карта сайта</a></span></div>
  </div></footer>`;
}
/* Полноэкранный поиск: пустое состояние с подсказками и промо, живые результаты при вводе */
const SRCH_HINTS=['Эфиопия Оромия','кофе для офиса','аренда кофемашины','Jetinno JL15','чай Эрл Грей','ремонт кофемашины','кофе оптом','дрип-пакеты'];
function searchPanel(){
  return `<div class="srch" id="srch" role="dialog" aria-modal="true" aria-label="Поиск по сайту">
    <div class="srch__top"><div class="wrap srch__in">
      <div class="srch__f">${I.search}
        <input id="sq" type="search" autocomplete="off" spellcheck="false" placeholder="Что ищем? Кофе, чай, кофемашину или услугу" aria-label="Поисковый запрос" aria-controls="sbody" aria-autocomplete="list">
        <button class="srch__clr" id="sclr" aria-label="Очистить">×</button>
      </div>
      <button class="srch__x" id="sx">Закрыть<kbd>Esc</kbd></button>
    </div></div>
    <div class="srch__body" id="sbody"><div class="wrap" id="sinner"></div></div>
  </div>`;
}
function authModal(){
  return `<div class="modal" id="auth" role="dialog" aria-modal="true" aria-labelledby="authTitle">
  <div class="modal__bg" data-close></div>
  <div class="modal__p">
    <button class="modal__x" data-close aria-label="Закрыть">×</button>

    <!-- шаг 1: e-mail или телефон -->
    <form data-step="pick" novalidate>
      <h3 class="display" id="authTitle" style="font-size:20px;margin-bottom:6px">Вход и регистрация</h3>
      <p class="muted" style="margin:0 0 18px;font-size:14px">Пароль не нужен: ${IDP.length?'войдите через сервис или получите код на почту':'пришлём код на почту'}.</p>
      ${IDP.length?BT_idpHtml('')+'<div class="or">или получить код</div>':''}
      <div class="field"><label for="aLogin" id="aLabel">E-mail или телефон</label>
        <input id="aLogin" name="login" type="email" inputmode="email" autocomplete="email" placeholder="mail@company.ru">
        <span class="hint" id="aHint">Пришлём код на почту — пароль не нужен</span></div>
      <div class="alert alert--info auth__note" id="aNote" hidden></div>
      <button class="btn btn--block" id="aSend" type="submit">Продолжить</button>
      <p class="muted" style="margin:14px 0 0;font-size:12px;line-height:1.5">Продолжая, вы принимаете <a class="link" href="/polzovatelskoe-soglashenie/" target="_blank" rel="noopener">пользовательское соглашение</a> и <a class="link" href="/politika-konfidencialnosti/" target="_blank" rel="noopener">политику обработки персональных данных</a>.</p>
    </form>

    <!-- шаг 2: код -->
    <div data-step="code" hidden>
      <button class="authback" id="aBack" type="button">← Изменить</button>
      <h3 class="display" style="font-size:20px;margin-bottom:6px">Введите код</h3>
      <p class="muted" style="margin:0 0 16px;font-size:14px">Отправили на <b id="aTo"></b>. Код действует 10 минут.</p>
      <div class="code4" id="aCode">
        <input inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Цифра 1">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 2">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 3">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 4">
      </div>
      <p class="err-form auth__err" id="aCodeErr" role="alert"></p>
      <p class="resend" id="aResend"></p>
    </div>

    <!-- шаг 3: имя для нового аккаунта -->
    <form data-step="name" hidden novalidate>
      <h3 class="display" style="font-size:20px;margin-bottom:6px">Как к вам обращаться</h3>
      <p class="muted" style="margin:0 0 16px;font-size:14px">Аккаунта с этим e-mail ещё нет — создадим. Имя подставим в заказы.</p>
      <div class="field"><label for="aName">Имя и фамилия *</label><input id="aName" name="name" autocomplete="name" maxlength="100"></div>
      <button class="btn btn--block" id="aFinish" type="submit">Готово</button>
    </form>

    <!-- шаг 4: вошли -->
    <div data-step="done" hidden>
      <div class="authu"><i id="aAv"></i><span><b id="aUName"></b><br><span class="muted" style="font-size:13px" id="aUId"></span></span></div>
      <a class="btn btn--block" href="/personal/">Личный кабинет</a>
      <button class="btn btn--block btn--ghost" style="margin-top:10px" id="aOut" type="button">Выйти</button>
    </div>
  </div></div>`;
}

/* ---------- mount ---------- */
document.addEventListener('DOMContentLoaded',()=>{
  const app = document.getElementById('app') || document.body;
  // шапку, drawer и футер отдаёт сервер (header.php/footer.php), JS дорисовывает только оверлеи
  if(!document.getElementById('hdr')) app.insertAdjacentHTML('afterbegin', header()+drawer());
  app.insertAdjacentHTML('beforeend', (document.querySelector('.ftr')?'':footer())+searchPanel()+authModal()+'<div class="toast" id="toast" role="status" aria-live="polite"></div>');

  /* ---- доступность и качество (аудит по WIG / ux-designer / craft-floor) ---- */
  /* скип-линк к содержимому */
  const hdrEl=document.getElementById('hdr');
  const mainEl=hdrEl && hdrEl.nextElementSibling && hdrEl.nextElementSibling.id!=='drawer' ? hdrEl.nextElementSibling : (document.getElementById('drawer')||{}).nextElementSibling;
  if(mainEl && !document.getElementById('main')){mainEl.id='main';mainEl.setAttribute('tabindex','-1');}
  app.insertAdjacentHTML('afterbegin','<a class="skip" href="#main">К содержанию</a>');
  /* связка label ↔ input внутри .field, автозаполнение и типы по подписи */
  let fid=0;
  document.querySelectorAll('.field').forEach(f=>{
    const lab=f.querySelector('label'), inp=f.querySelector('input,select,textarea');
    if(!lab||!inp) return;
    if(!inp.id) inp.id='f'+(++fid);
    if(!lab.htmlFor) lab.htmlFor=inp.id;
    if(lab.textContent.includes('*')) inp.required=true;
    const t=lab.textContent.toLowerCase();
    if(inp.tagName!=='INPUT') return;
    if(t.includes('телефон')){inp.type='tel';inp.inputMode='tel';inp.autocomplete=inp.autocomplete||'tel';}
    else if(t.includes('mail')){inp.type='email';inp.autocomplete=inp.autocomplete||'email';inp.spellcheck=false;}
    else if(t.includes('имя')){inp.autocomplete=inp.autocomplete||'name';}
    else if(t.includes('инн')){inp.inputMode='numeric';}
    else if(t.includes('код')){inp.spellcheck=false;inp.autocomplete='one-time-code';}
  });
  /* ============ Валидация форм ============
     Практика: проверяем на blur (не мешаем набирать), после первой ошибки —
     сразу на вводе, чтобы человек видел исправление. Ошибка рядом с полем,
     место под неё зарезервировано, фокус уводится на первую проблему. */
  const V={
    email:{test:v=>/^[^\s@]+@[^\s@]+\.[a-zA-Zа-яА-Я]{2,}$/.test(v), msg:'Проверьте адрес: нужен формат mail@company.ru'},
    tel:{test:v=>v.replace(/\D/g,'').length===11, msg:'Введите номер полностью: +7 и 10 цифр'},
    name:{test:v=>v.trim().length>=2, msg:'Как к вам обращаться? Минимум 2 символа'},
    inn:{test:v=>[10,12].includes(v.replace(/\D/g,'').length), msg:'ИНН состоит из 10 цифр у компании и 12 у ИП'},
    text:{test:v=>v.trim().length>0, msg:'Заполните поле'},
    addr:{test:v=>/[а-яёa-z]{2,}.*\d/i.test(v), msg:'Укажите номер дома'}
  };
  const kindOf = el => {
    if(el.dataset.v) return el.dataset.v;
    const lab=(el.closest('.field')?.querySelector('label')?.textContent||'').toLowerCase();
    if(el.type==='email'||lab.includes('mail')) return 'email';
    if(el.type==='tel'||lab.includes('телефон')) return 'tel';
    if(lab.includes('инн')) return 'inn';
    if(lab.includes('имя')||lab.includes('фамилия')) return 'name';
    return 'text';
  };
  /* маска +7 (999) 999-99-99 */
  const maskTel = el => {
    const fmt=v=>{let d=v.replace(/\D/g,'');
      if(d.startsWith('8'))d='7'+d.slice(1); if(!d.startsWith('7'))d='7'+d; d=d.slice(0,11);
      let r='+7'; if(d.length>1)r+=' ('+d.slice(1,4); if(d.length>=5)r+=') '+d.slice(4,7);
      if(d.length>=8)r+='-'+d.slice(7,9); if(d.length>=10)r+='-'+d.slice(9,11); return r;};
    el.addEventListener('focus',()=>{if(!el.value)el.value='+7 (';});
    el.addEventListener('input',()=>{el.value=fmt(el.value);});
    el.addEventListener('blur',()=>{if(el.value.replace(/\D/g,'').length<=1)el.value='';});
    el.addEventListener('keydown',e=>{if(e.key.length===1&&!/[\d+]/.test(e.key)&&!e.ctrlKey&&!e.metaKey)e.preventDefault();});
  };
  const fieldErr = (el,msg) => {
    const f=el.closest('.field')||el.parentNode;
    let e=f.querySelector('.err');
    if(!e){e=document.createElement('span');e.className='err';e.id='e'+Math.random().toString(36).slice(2,7);f.appendChild(e);}
    if(msg){f.classList.add('is-err');e.textContent=msg;el.setAttribute('aria-invalid','true');el.setAttribute('aria-describedby',e.id);}
    else{f.classList.remove('is-err');e.textContent='';el.removeAttribute('aria-invalid');el.removeAttribute('aria-describedby');}
  };
  window.BT_checkField = el => {
    if(el.disabled||el.type==='hidden'||el.closest('[hidden]')) return true;
    const v=el.value||'', req=el.required||/\*/.test(el.closest('.field')?.querySelector('label')?.textContent||'');
    if(!v.trim()){ if(req){fieldErr(el,'Это поле нужно заполнить');return false;} fieldErr(el,'');return true; }
    const k=kindOf(el), r=V[k];
    if(r&&!r.test(v)){fieldErr(el,r.msg);return false;}
    fieldErr(el,'');return true;
  };
  document.querySelectorAll('.field input,.field textarea,.field select').forEach(el=>{
    if(el.type==='file'||el.type==='checkbox'||el.type==='radio')return;
    if(kindOf(el)==='tel') maskTel(el);
    el.addEventListener('blur',()=>BT_checkField(el));
    el.addEventListener('input',()=>{ if(el.closest('.field')?.classList.contains('is-err')) BT_checkField(el); });
  });
  /* согласие с политикой — обязательный чекбокс */
  const checkAgree = box => {
    const w=box.closest('.check'); if(!w) return true;
    if(box.checked){w.classList.remove('is-err');box.removeAttribute('aria-invalid');return true;}
    w.classList.add('is-err');box.setAttribute('aria-invalid','true');return false;
  };
  document.querySelectorAll('.check input[type=checkbox]').forEach(b=>
    b.addEventListener('change',()=>{if(b.checked)checkAgree(b);}));

  /* submit: собираем все ошибки, ведём к первой */
  window.BT_validate = root => {
    root=root||document;
    let ok=true, first=null;
    root.querySelectorAll('.field input,.field textarea,.field select').forEach(el=>{
      if(el.type==='file'||el.type==='checkbox'||el.type==='radio')return;
      if(!BT_checkField(el)){ok=false;first=first||el;}
    });
    root.querySelectorAll('.check input[type=checkbox]').forEach(b=>{
      const lab=(b.closest('.check')?.textContent||'').toLowerCase();
      if(/соглас|принима|ознакомл/.test(lab)&&!checkAgree(b)){ok=false;first=first||b;}
    });
    if(first){ first.focus({preventScroll:true});
      first.scrollIntoView({behavior:'smooth',block:'center'});
      BT_toast('Проверьте выделенные поля'); }
    return ok;
  };
  /* перехват отправки: и кнопки, и ссылки-заглушки прототипа */
  document.addEventListener('click',e=>{
    const b=e.target.closest('a.btn,button.btn'); if(!b) return;
    const box=b.closest('.card,.wr__f,.blk,.modal__p,.revform,form,.subcard'); if(!box) return;
    const t=(b.textContent||'').toLowerCase();
    if(!/отправ|подпис|заказ|вызвать|сохранить|подтвердить|зарегистр|продолжить|оформить/.test(t)) return;
    if(!box.querySelector('.field input,.field textarea,.check input[type=checkbox]')) return;
    if(!BT_validate(box)){ e.preventDefault(); e.stopPropagation(); }
  },true);
  document.querySelectorAll('form').forEach(f=>{f.setAttribute('novalidate','');
    f.addEventListener('submit',e=>{ if(!BT_validate(f)) e.preventDefault(); });});

  /* кнопки ± без текста — имя для скринридера */
  document.addEventListener('focusin',e=>{const b=e.target.closest('.qty button');if(b&&!b.getAttribute('aria-label'))b.setAttribute('aria-label',b.dataset.d==='-'?'Уменьшить количество':'Увеличить количество');});
  document.querySelectorAll('.qty button').forEach(b=>{if(!b.getAttribute('aria-label'))b.setAttribute('aria-label',b.dataset.d==='-'?'Уменьшить количество':'Увеличить количество');});
  /* блокировка прокрутки под drawer/модалкой, Esc, возврат фокуса */
  let lastFocus=null;
  /* Блокировка фона под любым оверлеем: drawer, модалка, поиск, мобильный фильтр, галерея.
     Считаем по факту открытых слоёв, чтобы закрытие одного не разблокировало страницу под другим. */
  const OVERLAYS='.drawer.open,.modal.open,.srch.open,.filters.open,[data-overlay].open';
  const anyOpen=()=>!!document.querySelector(OVERLAYS);
  const lock=on=>{
    const want = on===undefined ? anyOpen() : (on || anyOpen());
    if(want&&!document.body.classList.contains('is-locked')){
      const sbw=window.innerWidth-document.documentElement.clientWidth;
      document.documentElement.style.setProperty('--sbw',(CSS.supports&&CSS.supports('scrollbar-gutter','stable')?0:sbw)+'px');
    }
    document.body.classList.toggle('is-locked',want);
    document.documentElement.classList.toggle('is-locked',want);
  };
  window.BT_lock=lock;
  /* страховка: любой элемент, получивший класс open, пересчитывает блокировку */
  new MutationObserver(()=>lock()).observe(document.body,{subtree:true,attributes:true,attributeFilter:['class']});
  const watch=(el,focusSel)=>{
    new MutationObserver(()=>{const o=el.classList.contains('open');lock();
      if(o){lastFocus=document.activeElement;const f=el.querySelector(focusSel);if(f)setTimeout(()=>f.focus(),30);}
      else if(lastFocus&&!anyOpen()){lastFocus.focus();lastFocus=null;}
    }).observe(el,{attributes:true,attributeFilter:['class']});
  };
  document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;document.querySelectorAll('.drawer.open,.modal.open').forEach(x=>x.classList.remove('open'));});

  /* mega */
  const mega=document.getElementById('mega'), cb=document.getElementById('catbtn'), mp=document.getElementById('megaPanel');
  let cur=1; mp.innerHTML=megaPanel(cur);
  const mcats=document.getElementById('megaCats');
  if(!mcats.children.length) mcats.innerHTML=CATS.map((c,i)=>`<button aria-selected="${i===cur}" data-i="${i}">${c.t}</button>`).join('');
  let mHoverAt=0;
  cb.addEventListener('click',()=>{
    // меню только что открылось наведением — клик по той же кнопке не должен его сразу закрыть
    if(mega.classList.contains('open') && Date.now()-mHoverAt<600) return;
    const o=mega.classList.toggle('open');
    document.getElementById('hdr').classList.toggle('mega-on',o);cb.setAttribute('aria-expanded',o);});
  let mHide;
  const hdrEl2=document.getElementById('hdr');
  const mOpen=()=>{clearTimeout(mHide);if(!mega.classList.contains('open'))mHoverAt=Date.now();mega.classList.add('open');hdrEl2.classList.add('mega-on');cb.setAttribute('aria-expanded',true);};
  const mClose=()=>{mHide=setTimeout(()=>{mega.classList.remove('open');hdrEl2.classList.remove('mega-on');cb.setAttribute('aria-expanded',false);},260);};
  cb.addEventListener('mouseenter',mOpen);cb.addEventListener('mouseleave',mClose);
  mega.addEventListener('mouseenter',()=>clearTimeout(mHide));mega.addEventListener('mouseleave',mClose);
  /* курсор идёт от кнопки вниз по диагонали и задевает строку меню — не закрываем мгновенно */
  document.querySelector('.hdr__nav').addEventListener('mouseenter',()=>{ if(mega.classList.contains('open')) clearTimeout(mHide); });
  /* задержался на пункте меню дольше 450 мс — значит хочет именно его: закрываем каталог */
  let navT;
  document.querySelectorAll('.hdr__nav .nav-i').forEach(n=>{
    n.addEventListener('mouseenter',()=>{ if(!mega.classList.contains('open'))return;
      navT=setTimeout(()=>{mega.classList.remove('open');hdrEl2.classList.remove('mega-on');cb.setAttribute('aria-expanded',false);},450); });
    n.addEventListener('mouseleave',()=>clearTimeout(navT));
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){mega.classList.remove('open');document.getElementById('hdr').classList.remove('mega-on');cb.setAttribute('aria-expanded',false);}});
  window.addEventListener('scroll',()=>{if(mega.classList.contains('open')){mega.classList.remove('open');document.getElementById('hdr').classList.remove('mega-on');cb.setAttribute('aria-expanded',false);}},{passive:true});
  document.getElementById('megaCats').addEventListener('mouseover',e=>{const b=e.target.closest('button');if(!b)return;
    cur=+b.dataset.i;[...b.parentNode.children].forEach(x=>x.setAttribute('aria-selected',x===b));mp.innerHTML=megaPanel(cur);});
  document.addEventListener('click',e=>{if(!e.target.closest('#mega')&&!e.target.closest('#catbtn')){mega.classList.remove('open');document.getElementById('hdr').classList.remove('mega-on');cb.setAttribute('aria-expanded',false);}});

  /* drawer */
  const dr=document.getElementById('drawer'), burger=document.getElementById('burger'), dn=document.getElementById('dn');
  watch(dr,'.dn__a');
  const dnStack=[];
  const dnCur=()=>dn.querySelector('.dn__p.is-on');
  /* кабинет и счётчики — из данных посетителя в браузере, а не в разметке: меню кешируется */
  const dnFill=()=>{
    const q=s=>dn.querySelector(s), u=window.BT_USER;
    if(u){ q('[data-dme-name]').textContent=u.name||u.email; q('[data-dme-sub]').textContent='Личный кабинет';
      q('[data-dme-i]').textContent=(u.name||u.email||'?').trim().charAt(0).toUpperCase(); }
    q('[data-dcnt="fav"]').textContent=(window.BT_FAV||[]).length||'';
    q('[data-dcnt="cmp"]').textContent=(window.BT_CMP||[]).length||'';
  };
  new MutationObserver(()=>{ const o=dr.classList.contains('open');
    burger.setAttribute('aria-expanded',o); burger.setAttribute('aria-label',o?'Закрыть меню':'Меню');
    if(!o){ dn.querySelectorAll('.dn__p').forEach(p=>p.classList.remove('is-on','is-past')); document.getElementById('dp0').classList.add('is-on'); dnStack.length=0; }
  }).observe(dr,{attributes:true,attributeFilter:['class']});
  const dnOpen=()=>{ dr.style.setProperty('--dtop',Math.max(0,document.getElementById('hdr').getBoundingClientRect().bottom)+'px'); dnFill(); dr.classList.add('open'); };
  burger.addEventListener('click',()=>dr.classList.contains('open')?dr.classList.remove('open'):dnOpen());
  dn.addEventListener('click',e=>{
    const go=e.target.closest('[data-dgo]'), back=e.target.closest('[data-dback]');
    if(go){ const cur=dnCur(), next=document.getElementById(go.dataset.dgo); dnStack.push(cur);
      cur.classList.replace('is-on','is-past'); next.scrollTop=0; next.classList.add('is-on'); return; }
    if(back){ const cur=dnCur(), prev=dnStack.pop(); if(!prev) return; cur.classList.remove('is-on'); prev.classList.remove('is-past'); prev.classList.add('is-on'); return; }
    if(e.target.closest('a[href]')) dr.classList.remove('open');
  });
  if(location.hash==='#menu') dnOpen();

  /* ---------- полноэкранный поиск ---------- */
  const sp=document.getElementById('srch'), sq=document.getElementById('sq'),
        sinner=document.getElementById('sinner'), sclr=document.getElementById('sclr');
  let sLast=null;
  const recent=()=>{try{return JSON.parse(localStorage.getItem('bt_recent')||'[]')}catch(e){return []}};
  const pushRecent=q=>{try{const r=recent().filter(x=>x!==q);r.unshift(q);localStorage.setItem('bt_recent',JSON.stringify(r.slice(0,5)));}catch(e){}};

  window.BT_search=()=>{ sLast=document.activeElement; sp.classList.add('open'); lock(true);
    sq.value=''; sclr.classList.remove('show'); paintEmpty(); setTimeout(()=>sq.focus(),60); };
  const closeSearch=()=>{ sp.classList.remove('open'); lock();
    if(sLast){sLast.focus();sLast=null;} };

  // картинки промо — фото товаров каталога по символьному коду (не со старого сайта)
  const byCode=code=>Object.values(BT_PRODUCTS).flat().find(x=>x.code===code);
  function paintEmpty(){
    const rec=recent();
    sinner.innerHTML=`<div class="srch__grid">
      <div>
        ${rec.length?`<h4>Вы искали</h4><div class="srch__chips">${rec.map(q=>`<button class="rec" data-q="${q}">${q}</button>`).join('')}</div>`:''}
        <h4>Часто ищут</h4>
        <div class="srch__chips">${SRCH_HINTS.map(q=>`<button data-q="${q}">${q}</button>`).join('')}</div>
        <h4>Разделы</h4>
        <div class="srch__secs">${CATS.map(c=>`<a href="${c.h}">${c.promo?`<img src="${c.promo.img}" alt="" loading="lazy">`:''}${c.t}</a>`).join('')}</div>
      </div>
      <div class="srch__promo">
        <h4>Предложения</h4>
        <a class="spromo spromo--lime" href="/podpiska/">
          <span class="k">Подписка</span><b>Кофемашина бесплатно</b><span>При заказе от 3 кг кофе в месяц. Обслуживание и ремонт наши.</span>
          ${byCode('jetinno-jl-05')?`<img src="${byCode('jetinno-jl-05').img}" alt="" loading="lazy">`:''}</a>
        <a class="spromo spromo--esp" href="${byCode('botanica-efiopiya-oromiya')?.url||'/magazin/kofe/'}">
          <span class="k">Зерно месяца</span><b>Эфиопия Оромия, Q 82,5</b><span>2 687 ₽ за кг, от 30 кг — 1 940 ₽</span>
          ${byCode('botanica-efiopiya-oromiya')?`<img src="${byCode('botanica-efiopiya-oromiya').img}" alt="" loading="lazy">`:''}</a>
        <a class="spromo spromo--dark" href="/arenda-kofemashin/#calc">
          <span class="k">Калькулятор</span><b>Подберём машину под нагрузку</b><span>Ответьте на 4 вопроса и увидите цену аренды</span></a>
      </div>
    </div>`;
  }

  const esc=t=>t.replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
  const mark=(t,q)=>{const i=t.toLowerCase().indexOf(q.toLowerCase());
    return i<0?esc(t):esc(t.slice(0,i))+'<mark>'+esc(t.slice(i,i+q.length))+'</mark>'+esc(t.slice(i+q.length));};

  const PAGES=window.BT_PAGES||[];

  function paintResults(q){
    const ql=q.toLowerCase();
    const prods=Object.values(BT_PRODUCTS).flat()
      .filter(m=>(m.n+' '+m.par).toLowerCase().includes(ql)).slice(0,6);
    const pages=PAGES.filter(p=>(p.t+' '+p.d+' '+(p.k||'')).toLowerCase().includes(ql)).slice(0,5);
    const posts=(window.BT_POSTS||[]).filter(p=>(p.t+' '+p.lead).toLowerCase().includes(ql)).slice(0,3);
    if(!prods.length&&!pages.length&&!posts.length){
      sinner.innerHTML=`<div class="sempty"><b>Ничего не нашли по запросу «${esc(q)}»</b>
        <p>Попробуйте короче или загляните в <a class="link" href="/magazin/">каталог</a>. Можно позвонить: <a class="link" href="tel:${CO.tel1}">${CO.tel1f}</a></p></div>`;
      return;
    }
    sinner.innerHTML=`<div class="srch__grid"><div>
      ${prods.length?`<h4>Товары · ${prods.length}</h4><div class="sres">${prods.map(m=>
        `<a href="${m.url}"><img src="${m.img}" alt="" loading="lazy"><span><span class="n">${mark(m.n,q)}</span><span class="p">${esc(m.par)}</span></span><span class="pr">${m.p?BT_fmt(m.p):'по запросу'}</span></a>`).join('')}</div>`:''}
      ${pages.length?`<h4>Разделы</h4><div class="sres">${pages.map(p=>
        `<a href="${p.u}"><span><span class="n">${mark(p.t,q)}</span><span class="p">${esc(p.d)}</span></span></a>`).join('')}</div>`:''}
      ${posts.length?`<h4>Журнал</h4><div class="sres">${posts.map(p=>
        `<a href="${BT_postUrl(p)}"><span><span class="n">${mark(p.t,q)}</span><span class="p">${BT_postDate(p.d)} · ${esc(p.cat)}</span></span></a>`).join('')}</div>`:''}
      </div>
      <div class="srch__promo"><h4>Не нашли нужное?</h4>
        <a class="spromo spromo--dark" href="/kontakty/#form"><span class="k">Подбор</span><b>Спросите менеджера</b><span>Ответим за 5 минут в рабочее время и подберём под задачу</span></a>
      </div></div>`;
  }

  let sTimer;
  sq && sq.addEventListener('input',()=>{
    const q=sq.value.trim(); sclr.classList.toggle('show',!!q);
    clearTimeout(sTimer);
    sTimer=setTimeout(()=>{ q.length<2?paintEmpty():paintResults(q); },200);   /* дебаунс 200 мс */
  });
  sq && sq.addEventListener('keydown',e=>{ if(e.key==='Enter'&&sq.value.trim().length>1){ pushRecent(sq.value.trim()); location.href='/search/?q='+encodeURIComponent(sq.value.trim()); }});
  sclr && sclr.addEventListener('click',()=>{sq.value='';sclr.classList.remove('show');paintEmpty();sq.focus();});
  document.getElementById('sx').addEventListener('click',closeSearch);
  sinner.addEventListener('click',e=>{const b=e.target.closest('[data-q]');if(!b)return;
    sq.value=b.dataset.q;sclr.classList.add('show');pushRecent(b.dataset.q);paintResults(b.dataset.q);sq.focus();});
  const sb=document.getElementById('srchBtn'); if(sb) sb.addEventListener('click',BT_search);
  const sbm=document.getElementById('srchBtnM'); if(sbm) sbm.addEventListener('click',()=>{dr.classList.remove('open');BT_search();});
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&sp.classList.contains('open')){closeSearch();return;}
    if((e.key==='k'&&(e.metaKey||e.ctrlKey))||(e.key==='/'&&!/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName))){
      e.preventDefault(); sp.classList.contains('open')?closeSearch():BT_search(); }
  });
  if(location.hash==='#search') BT_search();

  /* ---------- авторизация: e-mail или телефон → код на почту → вход; новый e-mail — ещё имя ---------- */
  const au=document.getElementById('auth');watch(au,'input,button');
  const $a=s=>au.querySelector(s);
  const step = n => { au.querySelectorAll('[data-step]').forEach(x=>x.hidden=x.dataset.step!==n);
    const f=au.querySelector(`[data-step="${n}"] input,[data-step="${n}"] .btn`); if(f) setTimeout(()=>f.focus(),40); };
  let pending=null, timer=null, busy=false;

  window.BT_auth=login=>{ au.classList.add('open');
    if(typeof login==='string'&&login&&!USER){ $a('#aLogin').value=login; step('pick'); send(login); return; }
    step(USER?'done':'pick'); if(USER) fillDone(); };
  au.addEventListener('click',e=>{if(e.target.closest('[data-close]'))au.classList.remove('open');});

  function fillDone(){ if(!USER)return;
    $a('#aAv').textContent=((USER.name||USER.email||'?').trim()[0]||'?').toUpperCase();
    $a('#aUName').textContent=USER.name||'Без имени';
    $a('#aUId').textContent=USER.email||''; }
  /* вошли: страницы кабинета и оформления перерисовывает сервер под покупателя */
  function done(r,msg){
    if(r.sessid) window.BT_SID=r.sessid;
    BT_login(r.user); clearInterval(timer);
    if(location.pathname.startsWith('/personal/')){ location.reload(); return; }
    fillDone(); step('done'); BT_toast(msg);
  }
  const loginErr = msg => fieldErr($a('#aLogin'),msg);
  const note = msg => { const n=$a('#aNote'); n.hidden=!msg; n.textContent=msg||''; };

  function tick(sec){ const el=$a('#aResend'); clearInterval(timer);
    const run=()=>{ if(sec<=0){ clearInterval(timer); el.innerHTML='<a class="link" href="#" id="aAgain">Отправить код ещё раз</a>';
        el.querySelector('#aAgain').onclick=ev=>{ev.preventDefault();send(pending.login,true);}; return; }
      el.textContent='Отправить код ещё раз можно через '+sec+' с'; sec--; };
    run(); timer=setInterval(run,1000); }

  function send(login,again){
    if(busy) return; busy=true; $a('#aSend').disabled=true;
    BT_authPost({action:'send',login}).then(r=>{
      if(r.ok){
        pending={login,to:r.to}; $a('#aTo').textContent=r.to; $a('#aCodeErr').textContent='';
        cells.forEach(i=>i.value=''); step('code'); tick(r.wait||59);
        if(again) BT_toast('Код отправлен повторно'); return;
      }
      if(r.wait&&pending&&pending.login===login){ step('code'); tick(r.wait); return; }
      if(again){ $a('#aCodeErr').textContent=r.message||'Не получилось отправить код'; return; }
      if(r.need==='email'){ const i=$a('#aLogin'); i.value=''; i.dispatchEvent(new Event('input')); note(r.message); i.focus(); return; }
      if(r.errors&&r.errors.login) loginErr(r.errors.login); else loginErr(r.message||'Не получилось отправить код');
    }).catch(()=>loginErr('Нет связи с сервером — попробуйте ещё раз'))
      .finally(()=>{busy=false;$a('#aSend').disabled=false;});
  }

  /* поле само понимает, что вводят: цифры → телефон с маской +7, иначе e-mail */
  (function(){
    const inp=$a('#aLogin'), lab=$a('#aLabel'), hint=$a('#aHint');
    const telFmt = v => { let d=v.replace(/\D/g,''); if(!d) return '';
      if(d[0]==='8') d='7'+d.slice(1); if(d[0]!=='7') d='7'+d; d=d.slice(0,11);
      let r='+7'; if(d.length>1)r+=' ('+d.slice(1,4); if(d.length>=5)r+=') '+d.slice(4,7);
      if(d.length>=8)r+='-'+d.slice(7,9); if(d.length>=10)r+='-'+d.slice(9,11); return r; };
    const setMode = m => {
      if(inp.dataset.mode===m) return; inp.dataset.mode=m;
      if(m==='tel'){ inp.type='tel'; inp.inputMode='tel'; inp.autocomplete='tel';
        lab.textContent='Телефон'; hint.textContent='SMS не подключены — код придёт на e-mail, привязанный к номеру'; }
      else { inp.type='email'; inp.inputMode='email'; inp.autocomplete='email';
        lab.textContent='E-mail'; hint.textContent='Пришлём код на почту — пароль не нужен'; }
    };
    inp.addEventListener('input',()=>{
      const v=inp.value;
      if(/^[\d+ ()-]*$/.test(v) && /\d/.test(v)){ setMode('tel'); const f=telFmt(v); if(f!==v) inp.value=f; }
      else setMode('mail');
    });
    inp.addEventListener('blur',()=>{ if(inp.dataset.mode==='tel' && inp.value.replace(/\D/g,'').length<=1) inp.value=''; });
    setMode('mail');
  })();

  $a('[data-step="pick"]').addEventListener('submit',e=>{
    const bad=e.defaultPrevented; e.preventDefault(); if(bad) return;
    const inp=$a('#aLogin'), v=inp.value.trim();
    if(!v){ loginErr('Введите e-mail или телефон'); inp.focus(); return; }
    note(''); send(v,false);
  });
  $a('#aBack').addEventListener('click',()=>{clearInterval(timer);step('pick');});

  /* 4 ячейки кода: только цифры, автопереход, Backspace назад, вставка из буфера */
  const cells=[...au.querySelectorAll('#aCode input')];
  cells.forEach((c,i)=>{
    c.addEventListener('input',()=>{ c.value=c.value.replace(/\D/g,'').slice(0,1);
      if(c.value&&i<3) cells[i+1].focus();
      if(cells.every(x=>x.value)) submitCode(); });
    c.addEventListener('keydown',e=>{ if(e.key==='Backspace'&&!c.value&&i>0){cells[i-1].focus();cells[i-1].value='';} });
    c.addEventListener('paste',e=>{ const d=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,4);
      if(!d) return; e.preventDefault(); d.split('').forEach((ch,k)=>cells[k]&&(cells[k].value=ch));
      cells[Math.min(d.length,3)].focus(); if(d.length===4) submitCode(); });
  });
  function submitCode(){
    if(busy) return; busy=true;
    const w=$a('#aCode'), err=$a('#aCodeErr');
    BT_authPost({action:'verify',code:cells.map(c=>c.value).join('')}).then(r=>{
      if(r.ok&&r.user) return done(r,'С возвращением, '+((r.user.name||'').split(' ')[0]||r.user.email));
      if(r.ok&&r.need==='name'){ clearInterval(timer); step('name'); return; }
      err.textContent=r.message||'Неверный код';
      w.classList.add('is-err');
      setTimeout(()=>{w.classList.remove('is-err');cells.forEach(c=>{c.value='';c.disabled=!!r.expired;});if(!r.expired)cells[0].focus();},420);
      if(r.expired){ clearInterval(timer); $a('#aResend').innerHTML='<a class="link" href="#" id="aAgain">Отправить новый код</a>';
        $a('#aAgain').onclick=ev=>{ev.preventDefault();cells.forEach(c=>c.disabled=false);send(pending.login,true);}; }
    }).catch(()=>{err.textContent='Нет связи с сервером — попробуйте ещё раз';})
      .finally(()=>{busy=false;});
  }
  $a('[data-step="name"]').addEventListener('submit',e=>{
    const bad=e.defaultPrevented; e.preventDefault(); if(bad||busy) return;
    const n=$a('#aName'); busy=true; $a('#aFinish').disabled=true;
    BT_authPost({action:'register',name:n.value.trim()}).then(r=>{
      if(r.ok) return done(r,'Аккаунт создан');
      if(r.errors&&r.errors.name) fieldErr(n,r.errors.name);
      else { BT_toast(r.message||'Не получилось создать аккаунт'); if(r.expired) step('pick'); }
    }).catch(()=>BT_toast('Нет связи с сервером — попробуйте ещё раз'))
      .finally(()=>{busy=false;$a('#aFinish').disabled=false;});
  });
  $a('#aOut').addEventListener('click',()=>BT_logout());
  document.addEventListener('click',e=>{ if(e.target.closest('[data-logout]')){ e.preventDefault(); BT_logout(); } });

  BT_authUpdate();
  if(location.hash==='#auth'||document.querySelector('[data-auth-open]')) BT_auth();
  document.addEventListener('click',e=>{ const b=e.target.closest('[data-auth]'); if(!b) return; e.preventDefault(); BT_auth(); });
  /* «Кабинет» в шапке и меню: гостя ведём в окно входа, покупателя — в кабинет */
  document.querySelectorAll('.hact[href="/personal/"],.dn__me').forEach(a=>
    a.addEventListener('click',e=>{ if(!USER){ e.preventDefault(); document.getElementById('drawer')?.classList.remove('open'); BT_auth(); } }));

  /* toast + add to cart */
  const t=document.getElementById('toast');let tm;
  window.BT_toast=msg=>{t.innerHTML=msg;t.classList.add('show');clearTimeout(tm);tm=setTimeout(()=>t.classList.remove('show'),3200);};
  /* вернулись от сервиса входа с ошибкой: причина — тостом, окно входа — сразу */
  if(/[?&]auth_service_error=/.test(location.search)){
    history.replaceState(null,'',location.pathname+location.search.replace(/[?&]auth_service_(id|error)=[^&]*/g,'').replace(/^&/,'?')+location.hash);
    const e=window.BT_AUTH_ERR||{};
    if(!USER){ BT_auth(); const n=document.getElementById('aNote'), i=document.getElementById('aLogin');
      n.hidden=false; n.textContent=e.msg||'Не получилось войти через сервис. Попробуйте ещё раз или получите код на почту.';
      if(e.email){ i.value=e.email; i.dispatchEvent(new Event('input')); } }
  }
  if(!USER&&IDP.length) document.querySelectorAll('[data-idp-row]').forEach(el=>{ el.innerHTML=BT_idpHtml(el.dataset.idpRow); const b=el.closest('[hidden]'); if(b) b.hidden=false; });
  BT_cartUpdate(false);
  BT_cmpUpdate();
  const rerender = id => {
    const m=BT_find(id); if(!m) return;
    document.querySelectorAll(`.pc[data-pc="${id}"]`).forEach(pc=>{
      const foot=pc.querySelector('.pc__foot'); if(!foot) return;
      const wasIn=document.activeElement, key=wasIn&&foot.contains(wasIn)?wasIn.dataset.q:null;
      /* перерисовываем только цену и контрол: высота карточки не меняется, страница не дёргается */
      const cur=foot.querySelector('.addq'), q0=CART[id]||0;
      if(cur && q0>0){
        /* степпер уже на месте: меняем только число и цену — ничего не пересобираем */
        const u=BT_unit(m)==='kg'?'кг':'шт';
        cur.querySelector('b').innerHTML=q0+'<i>'+u+'</i>';
        const box=foot.querySelector('div');
        if(box){ const t=BT_tier(m,q0);
          box.innerHTML=(m.old?`<span class="price--old">${BT_fmt(m.old*q0)}</span>`:'')+
            `<span class="price">${m.p?BT_fmt((m.bulk?t.p:m.p)*q0):'По запросу'}`+
            (m.unit?`<s>${m.unit}</s>`:m.bulk?`<s>${BT_fmt(t.p)} за кг${q0>1?` × ${q0} кг`:''}</s>`:
              (q0>1?`<s>${BT_fmt(m.p)} × ${q0} шт</s>`:(m.pre?'<s>предзаказ</s>':'')))+'</span>'; }
      } else {
        const keep=[...foot.children].filter(n=>n.tagName==='META'||n.tagName==='LINK').map(n=>n.outerHTML).join('');
        foot.innerHTML=keep+BT_footInner(m);
        const nq=foot.querySelector('.addq'); if(nq) nq.classList.add('addq--new');
      }
      const q=CART[id]||0;
      pc.querySelectorAll(`[data-packs="${id}"] button`).forEach(x=>x.setAttribute('aria-pressed',+x.dataset.kg===q));
      if(key){ const b=foot.querySelector(`.addq [data-q="${key}"]`); if(b) b.focus({preventScroll:true}); }
    });
  };
  // карточки приходят с сервера в состоянии «не в корзине» — подтягиваем степперы по корзине
  Object.keys(CART).forEach(rerender);
  // корзина изменилась не отсюда (откат после ошибки сервера, другая вкладка кода) — перерисовать карточку
  document.addEventListener('bt:cart',e=>{ if(e.detail) rerender(e.detail.id); });
  document.addEventListener('click',e=>{const b=e.target.closest('[data-add]');if(!b||!b.closest('.pc'))return;
    const id=b.dataset.add; BT_cartSet(id,(CART[id]||0)+1); rerender(id);});
  document.addEventListener('click',e=>{const b=e.target.closest('[data-packs] button');if(!b)return;
    const id=b.closest('[data-packs]').dataset.packs, kg=+b.dataset.kg;
    BT_cartSet(id,kg); rerender(id);
    document.querySelectorAll(`[data-packs="${id}"] button`).forEach(x=>x.setAttribute('aria-pressed',+x.dataset.kg===kg));
    const t=BT_tier(BT_find(id),kg), pct=BT_tierPct(BT_find(id),t);
    BT_toast(`${kg} кг в корзине${pct?` · цена ${BT_fmt(t.p)} за кг, выгода ${pct}%`:''}`);});
  document.addEventListener('click',e=>{const b=e.target.closest('.addq button');if(!b)return;
    const id=b.closest('.addq').dataset.id, q=(CART[id]||0)+(b.dataset.q==='-'?-1:1);
    BT_cartSet(id,q); if(b.closest('.pc')) rerender(id);});
  document.addEventListener('click',e=>{const f=e.target.closest('.pc__fav');if(!f)return;
    const pc=f.closest('[data-pc]'); if(!pc) return;
    const on=BT_favToggle(pc.dataset.pc);
    BT_toast(on?'Добавлено в <a href="/personal/favorites/">избранное</a>':'Убрано из избранного');});
  BT_favUpdate();
  document.addEventListener('click',e=>{const c=e.target.closest('.pc__cmpi');if(!c)return;
    const pc=c.closest('[data-pc]'); if(!pc) return;
    const on=BT_cmpToggle(pc.dataset.pc);
    BT_toast(on?'Товар добавлен к сравнению · <a href="/magazin/compare/">Сравнить</a>':'Товар убран из сравнения');});

  /* счётчик цифр: <b data-count="16">16 лет</b> — один проход при появлении, reduced-motion → сразу итог */
  const rm=matchMedia('(prefers-reduced-motion:reduce)').matches;
  const counters=document.querySelectorAll('[data-count]');
  if(counters.length&&!rm&&'IntersectionObserver' in window){
    const io=new IntersectionObserver(es=>es.forEach(en=>{if(!en.isIntersecting)return;io.unobserve(en.target);
      const el=en.target,txt=el.textContent,raw=String(el.dataset.count),target=parseFloat(raw.replace(',','.')),dec=(raw.split(/[.,]/)[1]||'').length,sep=raw.includes(',')?',':'.';
      const suffix=txt.replace(/^[\d.,]+/,''),t0=performance.now(),dur=1200;
      const step=now=>{const p=Math.min(1,(now-t0)/dur),e=1-Math.pow(1-p,3),v=target*e;
        el.textContent=(dec?v.toFixed(dec).replace('.',sep):Math.round(v).toLocaleString('ru-RU'))+suffix;
        if(p<1)requestAnimationFrame(step);else el.textContent=txt;};
      requestAnimationFrame(step);}),{threshold:.6});
    counters.forEach(c=>io.observe(c));
  }

  /* qty */
  document.addEventListener('click',e=>{const b=e.target.closest('.qty button');if(!b)return;
    const i=b.parentNode.querySelector('input');let v=+i.value||1;
    /* «минус» на единице — это «убрать из корзины», а не тупик */
    if(b.dataset.d==='-'&&v<=1){ i.dispatchEvent(new CustomEvent('bt:qtyzero',{bubbles:true})); return; }
    v+= b.dataset.d==='-'?-1:1;i.value=Math.max(1,v);i.dispatchEvent(new Event('change',{bubbles:true}));});

  /* SEO-разметка (крошки, FAQ, организация, canonical) выводится сервером — см. bt_og, bt_org_ld, bt_faq_ld */

  /* ---------- заявки: попап быстрой заявки и формы data-form → local/ajax/form.php ---------- */
  window.BT_fieldErr=fieldErr;
  const ld=document.getElementById('lead');
  if(ld){
    ld.addEventListener('click',e=>{if(e.target.closest('[data-close]'))ld.classList.remove('open');});
    new MutationObserver(()=>lock()).observe(ld,{attributes:true,attributeFilter:['class']});
    document.addEventListener('click',e=>{
      const b=e.target.closest('[data-lead]'); if(!b||b.closest('#lead')) return;
      e.preventDefault();
      const topic=b.dataset.lead||'Оставить заявку', f=ld.querySelector('form');
      f.hidden=false; ld.querySelector('.lead__ok').hidden=true;
      ld.querySelector('.lead__t').textContent=topic; f.elements.topic.value=topic;
      document.getElementById('drawer')?.classList.remove('open');
      ld.classList.add('open');
      /* на десктопе каретка сразу в первом пустом поле, на мобильном — нет (клавиатура закроет форму) */
      if(innerWidth>768) setTimeout(()=>{const i=[...f.querySelectorAll('.field input')].find(x=>!x.value); if(i) i.focus();},40);
    });
  }
  document.addEventListener('submit',async e=>{
    const f=e.target.closest('form[data-form]'); if(!f) return;
    /* невалидную форму уже остановил общий обработчик submit выше */
    if(e.defaultPrevented||f.dataset.busy){e.preventDefault();return;}
    e.preventDefault();
    const btn=f.querySelector('button[type=submit]'), fd=new FormData(f);
    fd.append('form',f.dataset.form); fd.append('page',location.pathname);
    f.dataset.busy='1'; if(btn) btn.disabled=true;
    try{
      const r=await fetch('/local/ajax/form.php',{method:'POST',body:fd,credentials:'same-origin'});
      const d=await r.json().catch(()=>({}));
      if(d.ok){
        if(f.closest('#lead')){ f.reset(); f.hidden=true; ld.querySelector('.lead__ok').hidden=false; ld.querySelector('.lead__ok .btn').focus(); }
        else location.href='/form-success/';
        return;
      }
      if(d.errors){ let first=null;
        Object.entries(d.errors).forEach(([k,msg])=>{const el=f.elements[k]; if(!el) return;
          if(el.type==='checkbox'){el.closest('.check')?.classList.add('is-err');} else fieldErr(el,msg); first=first||el;});
        if(first) first.focus();
        BT_toast('Проверьте выделенные поля');
      } else BT_toast(d.message||'Не удалось отправить заявку. Позвоните нам: '+(document.querySelector('.hdr__tel')?.textContent||''));
    }catch(err){ BT_toast('Нет связи с сервером. Попробуйте ещё раз'); }
    finally{ delete f.dataset.busy; if(btn) btn.disabled=false; }
  });

  /* ---------- главная: подбор кофемашины, вкладки «аренда/продажа», карта ---------- */
  const cfg=document.querySelector('[data-cfg]');
  if(cfg){
    const C=JSON.parse(cfg.dataset.cfg), q=s=>cfg.querySelector(s);
    cfg.addEventListener('click',e=>{const b=e.target.closest('.chipx'); if(!b) return;
      cfg.querySelectorAll('.chipx').forEach(x=>x.setAttribute('aria-pressed',x===b));
      const d=C[+b.dataset.i], a=q('[data-cfg-btn]');
      q('[data-cfg-img]').src=d.img; q('[data-cfg-img]').alt=d.m; q('[data-cfg-m]').textContent=d.m; q('[data-cfg-s]').textContent=d.s;
      q('[data-cfg-p]').textContent=d.p; q('[data-cfg-u]').textContent=d.u;
      if(d.lead){a.dataset.lead=d.lead; a.textContent='Оставить заявку';} else {delete a.dataset.lead; a.textContent=a.dataset.text; a.href=a.dataset.href;}
    });
  }
  document.querySelectorAll('.pill-tabs [data-tab]').forEach(b=>b.addEventListener('click',()=>{
    const sec=b.closest('section'); if(!sec||!sec.querySelector('[data-tabpane]')) return; /* у вкладок доставки на оформлении заказа свой обработчик */
    b.parentNode.querySelectorAll('[data-tab]').forEach(x=>x.setAttribute('aria-selected',x===b));
    sec.querySelectorAll('[data-tabpane]').forEach(p=>p.hidden=p.dataset.tabpane!==b.dataset.tab);
    BT_cmpUpdate(); BT_favUpdate();
  }));
  document.querySelectorAll('[data-ymap]').forEach(el=>BT_mapWidget(el));
  /* плитка разделов главной — слайдер, как в макете */
  BT_slider(document.getElementById('tiles'),{min:5,swiper:{breakpoints:{560:{slidesPerView:2},900:{slidesPerView:3},1200:{slidesPerView:5,spaceBetween:14}}}});
  /* 404: поиск открывает общий поиск по сайту с введённым запросом */
  const escq=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

  /* ---------- аренда: калькулятор — модели и пороги из ИБ rent, расход 8 г × чашек × 22 дня, цена зерна из каталога ---------- */
  const rc=document.querySelector('[data-rcalc]');
  if(rc){
    const C=JSON.parse(rc.dataset.rcalc), M=C.models, B=C.bean, q=s=>rc.querySelector(s), rng=q('[data-rc-cups]');
    const kgf=x=>String(Math.round(x*10)/10).replace('.',','), cost=kg=>Math.round(kg*BT_tier(B,kg).p);
    let cur=null;
    const draw=()=>{
      const cups=+rng.value, m=M.find(x=>x.cups>=cups)||M[M.length-1], need=cups*C.g*C.days/1000, free=need>=m.kg;
      cur={m,cups,need};
      q('[data-rc-n]').textContent=cups;
      rc.querySelectorAll('[data-rc-m]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.rcM===m.id));
      const img=q('[data-rc-img]'); if(img.getAttribute('src')!==m.img) img.src=m.img; img.alt=m.m;
      q('[data-rc-name]').textContent=m.m;
      q('[data-rc-s]').textContent=`До ${m.cups} чашек в день · ${m.aud}`;
      q('[data-rc-p]').textContent=free?'0 ₽':BT_fmt(m.price);
      q('[data-rc-alt]').textContent=free?`при заказе кофе от ${m.kg} кг в месяц. Или ${BT_fmt(m.price)} в месяц с фиксированной оплатой`
        :`или 0 ₽ при заказе кофе от ${m.kg} кг в месяц (≈ ${BT_fmt(cost(m.kg))} за кофе)`;
      q('[data-rc-note]').innerHTML=`Расход ≈ <b>${kgf(need)} кг</b> кофе в месяц: ${C.g} г × ${cups} чашек × ${C.days} рабочих дня. `
        +`Зерном <a class="link" href="${escq(B.url)}">${escq(B.n)}</a> — ≈ ${BT_fmt(cost(need))} в месяц.`
        +(cups>M[M.length-1].cups?' Для такой нагрузки подберём решение индивидуально — оставьте заявку.':'');
    };
    const setModel=id=>{const m=M.find(x=>x.id===id); if(m){rng.value=m.cups; draw();}};
    rng.addEventListener('input',draw);
    rc.addEventListener('click',e=>{const b=e.target.closest('[data-rc-m]'); if(b) setModel(b.dataset.rcM);});
    /* карточка модели на этой же странице ведёт в калькулятор с этой моделью */
    document.querySelectorAll('.rentp [data-pc] a').forEach(a=>a.addEventListener('click',()=>setModel(a.closest('[data-pc]').dataset.pc)));
    q('[data-rc-btn]').addEventListener('click',()=>{
      const f=document.querySelector('#form form'); if(!f||!cur) return;
      const sel=f.elements.model, msg=f.elements.message;
      if(sel){ sel.value=cur.m.m; sel.dispatchEvent(new Event('change',{bubbles:true})); }
      if(msg&&(!msg.value||msg.dataset.auto===msg.value)){ msg.value=msg.dataset.auto=`Расчёт на сайте: ≈ ${cur.cups} чашек в день, расход ≈ ${kgf(cur.need)} кг кофе в месяц`; }
    });
    draw();
  }

  /* ---------- ремонт: отмеченные симптомы — первой строкой поля «Что случилось», свой текст остаётся ---------- */
  const sy=document.querySelector('[data-symp]'), st=document.querySelector('[data-symp-to]');
  if(sy&&st) sy.addEventListener('change',()=>{
    const list=[...sy.querySelectorAll('input:checked')].map(x=>x.value).join('; '), own=st.value.replace(/^Симптомы:.*(\n|$)/,'');
    st.value=(list?'Симптомы: '+list+(own?'\n':''):'')+own;
    if(st.closest('.field')?.classList.contains('is-err')) BT_checkField(st);
  });

  /* ---------- подписка: объём — пороги бесплатной аренды, цена — оптовая сетка сорта; расчёт уходит в заявку ---------- */
  const sc=document.querySelector('[data-subcfg]');
  if(sc){
    const S=JSON.parse(sc.dataset.subcfg), q=s=>sc.querySelector(s), sel=q('[data-sub-sort]');
    let per=q('[data-sub-per][aria-pressed=true]');
    const calc=()=>{
      const vi=+(q('input[name=vol]:checked')?.value||0), v=S.vols[vi], c=S.coffee[+sel.value]||S.coffee[0];
      const sum=BT_tier(c,v.kg).p*v.kg, base=c.p*v.kg, cups=Math.round(v.kg*1000/8), p=per.textContent.toLowerCase();
      sc.querySelectorAll('.vol').forEach((l,i)=>{ l.classList.toggle('on',i===vi);
        const pct=BT_tierPct(c,BT_tier(c,S.vols[i].kg)); l.querySelector('[data-sub-pct]').textContent=pct?'−'+pct+'%':''; });
      q('[data-sub-kg]').textContent='Кофе, '+v.kg+' кг';
      q('[data-sub-sum]').textContent=BT_fmt(base);
      q('[data-sub-disc]').textContent=base>sum?'−'+BT_fmt(base-sum):'—';
      q('[data-sub-tot]').textContent=BT_fmt(sum);
      q('[data-sub-cup]').innerHTML=`Примерно <b>${cups.toLocaleString('ru-RU')} чашек</b> в месяц (8 г на чашку) · ${BT_fmt(Math.round(sum/cups))} за чашку · доставка ${p}`;
      q('[data-sub-msg]').value=`Объём: ${v.kg} кг в месяц (${v.m} — аренда 0 ₽); сорт: ${c.n}; доставка: ${p}; расчёт на сайте: ${sum} ₽ в месяц`;
    };
    sc.addEventListener('change',e=>{ if(e.target.name==='vol'||e.target===sel) calc(); });
    sc.addEventListener('click',e=>{ const b=e.target.closest('[data-sub-per]'); if(!b) return; per=b;
      sc.querySelectorAll('[data-sub-per]').forEach(x=>x.setAttribute('aria-pressed',x===b)); calc(); });
    calc();
  }

  /* ---------- подбор кофе: вопросы и правила — с сервера (local/parts/podbor-kofe.php), результат — товар каталога ---------- */
  const qz=document.querySelector('[data-quiz]');
  if(qz){
    const D=JSON.parse(qz.dataset.quiz), Q=D.q, A={}; let i=0, res=null;
    const word=n=>n===1?'вопрос':n<5?'вопроса':'вопросов';
    const pick=()=>{
      let k=A.vol==='xl'||(A.taste==='choco'&&A.price==='cost')?'botanica-vending'
        :A.dev==='filter'&&A.milk!=='milk'?'botanica-efiopiya-sidamo-1'
        :A.milk==='milk'?'botanica-milk'
        :A.taste==='fruit'?'botanica-efiopiya-oromiya'
        :A.taste==='choco'?'botanica-braziliya-santos':D.fallback;
      if(!D.res[k]||!BT_find(D.res[k].id)) k=D.fallback;
      const r=D.res[k]; return r&&BT_find(r.id)?{p:BT_find(r.id),why:r.why}:null;
    };
    const render=()=>{
      if(i>=Q.length) return result();
      const x=Q[i], left=Q.length-i-1;
      qz.innerHTML=`<div class="quiz__head"><div class="quiz__bar"><i style="width:${i/Q.length*100}%"></i></div><span class="n">${i+1} / ${Q.length}</span></div>
        <p class="quiz__q">${escq(x.q)}</p>${x.h?`<p class="quiz__hint">${escq(x.h)}</p>`:'<div style="height:12px"></div>'}
        <div class="quiz__opts">${x.o.map(o=>`<button type="button" data-v="${escq(o[0])}">${escq(o[1])}<small>${escq(o[2])}</small></button>`).join('')}</div>
        <div class="quiz__nav">${i?'<button type="button" class="authback" data-qb>← Назад</button>':'<span></span>'}<span>${left?'Осталось '+left+' '+word(left):'Последний вопрос'}</span></div>`;
    };
    const result=()=>{
      res=pick();
      if(!res){ qz.innerHTML='<p class="quiz__q">Не получилось подобрать сорт</p><p class="quiz__hint">Позвоните нам — подберём вместе.</p>'; return; }
      const p=res.p, t=p.bulk&&{m:5,l:10,xl:30}[A.vol]?BT_tier(p,{m:5,l:10,xl:30}[A.vol]):null;
      const tier=t&&t.p<p.p?` · от ${t.kg} кг — ${BT_fmt(t.p)}/кг`:'';
      qz.innerHTML=`<div class="quiz__head"><div class="quiz__bar"><i style="width:100%"></i></div><span class="n">готово</span></div>
        <p class="quiz__q" style="margin-bottom:22px">Вам подойдёт</p>
        <div class="quiz__res">
          <a href="${escq(p.url)}">${p.img?`<img src="${escq(p.img)}" alt="${escq(p.n)}">`:''}</a>
          <div>
            <h3 class="h3" style="font-size:20px;margin-bottom:10px"><a href="${escq(p.url)}">${escq(p.n)}</a></h3>
            <p style="margin:0 0 14px;color:#3A3A34;font-size:15px">${escq(res.why)}</p>
            <p class="muted" style="font-size:13.5px;margin:0 0 18px">${escq(p.par)}</p>
            <div class="row" style="gap:14px;margin-bottom:16px"><b style="font-family:Unbounded;font-size:24px">${BT_fmt(p.p)}</b><span class="muted" style="font-size:13px">за 1 кг${tier}</span></div>
            <div class="row" style="gap:10px"><button type="button" class="btn" data-qadd>В корзину</button><a class="btn btn--line" href="${escq(p.url)}">Подробнее о сорте</a></div>
          </div>
        </div>
        ${A.vol==='l'||A.vol==='xl'?'<div class="alert alert--info" style="margin-top:24px">При таком объёме кофемашина в аренду обойдётся в 0 ₽. <a class="link" href="/podpiska/">Посмотреть подписку</a></div>':''}
        <hr class="hr" style="margin:26px 0">
        <div class="row between" style="gap:14px"><button type="button" class="btn btn--ghost btn--sm" data-qagain>Пройти заново</button><a class="link" href="/kontakty/#form" data-lead="Подбор кофе">Хочу, чтобы подобрал человек →</a></div>`;
    };
    qz.addEventListener('click',e=>{
      const v=e.target.closest('[data-v]'), t=e.target;
      if(v){ A[Q[i].k]=v.dataset.v; i++; }
      else if(t.closest('[data-qb]')) i--;
      else if(t.closest('[data-qagain]')){ i=0; Object.keys(A).forEach(k=>delete A[k]); }
      else if(t.closest('[data-qadd]')&&res){ BT_cartSet(res.p.id,(BT_CART[res.p.id]||0)+1); BT_toast(`«${escq(res.p.n)}» в корзине · <a href="/personal/cart/">Оформить</a>`); return; }
      else return;
      render();
      if(qz.getBoundingClientRect().top<0) qz.scrollIntoView({behavior:'smooth',block:'start'});
    });
  }
  BT_phFit();

  /* ---------- личный кабинет: профиль, юрлица, адреса, повтор заказа → local/ajax/account.php ---------- */
  const accPost=data=>{ const fd=data instanceof FormData?data:new FormData();
    if(!(data instanceof FormData)) Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    fd.append('sessid',window.BT_SID||'');
    return fetch('/local/ajax/account.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()); };
  /* после сохранения страницу перерисовывает сервер, сообщение показываем уже на новой */
  try{ const m=sessionStorage.getItem('bt_toast'); if(m){ sessionStorage.removeItem('bt_toast'); setTimeout(()=>BT_toast(m),200); } }catch(e){}
  const reloadWith=msg=>{ try{sessionStorage.setItem('bt_toast',msg);}catch(e){} location.reload(); };
  const accFail=(f,r)=>{
    if(!r.errors){ BT_toast(r.message||'Не получилось сохранить — попробуйте ещё раз'); return; }
    let first=null;
    Object.entries(r.errors).forEach(([k,m])=>{ const el=f.elements[k]; if(el){ fieldErr(el,m); first=first||el; } });
    if(first) first.focus(); BT_toast('Проверьте выделенные поля');
  };
  const accForm=(f,action,ok)=>f&&f.addEventListener('submit',e=>{
    const bad=e.defaultPrevented; e.preventDefault(); if(bad||f.dataset.busy) return;
    const fd=new FormData(f), btn=f.querySelector('[type=submit]'); fd.append('action',action); f.dataset.busy='1'; if(btn) btn.disabled=true;
    accPost(fd).then(r=>r.ok?ok(r):accFail(f,r)).catch(()=>BT_toast('Нет связи с сервером — попробуйте ещё раз'))
      .finally(()=>{ delete f.dataset.busy; if(btn) btn.disabled=false; });
  });
  const fill=(f,d)=>Object.entries(d).forEach(([k,v])=>{ const el=f.elements[k]; if(!el||el.type==='checkbox') return; el.value=v??''; if(el.type!=='hidden') fieldErr(el,''); });
  accForm(document.getElementById('accProfile'),'profile',()=>reloadWith('Данные сохранены'));

  /* юрлица: одна форма на добавление и правку */
  const org=document.getElementById('orgForm');
  if(org){
    const add=document.getElementById('orgAdd');
    const open=d=>{ fill(org,{id:d.id||0,company:d.company,inn:d.inn,kpp:d.kpp,company_adr:d.company_adr}); org.hidden=false; add.hidden=true;
      org.scrollIntoView({behavior:'smooth',block:'center'}); if(innerWidth>768) setTimeout(()=>org.elements.company.focus({preventScroll:true}),60); };
    add.addEventListener('click',()=>open({}));
    org.querySelector('[data-org-cancel]').addEventListener('click',()=>{ org.hidden=true; add.hidden=false; });
    accForm(org,'company_save',()=>reloadWith('Реквизиты сохранены'));
    document.addEventListener('click',e=>{ const b=e.target.closest('[data-org-edit],[data-org-del]'); if(!b) return;
      const d=JSON.parse(b.closest('[data-org]').dataset.org);
      if(b.matches('[data-org-edit]')) return open(d);
      b.disabled=true;
      accPost({action:'company_del',id:d.id}).then(r=>r.ok?reloadWith('Юрлицо удалено'):BT_toast(r.message||'Не получилось удалить')).finally(()=>b.disabled=false);
    });
  }

  /* адреса: окно с полями; город — из местоположений Битрикса (как в оформлении заказа) */
  const am=document.getElementById('addrModal');
  if(am){
    const f=am.querySelector('form'), D=JSON.parse(am.dataset.ekb), ci=f.elements.city, ul=f.querySelector('.city ul'), box=document.getElementById('adrMap');
    let mapOn=false, cityT=0, cityN=0;
    const cityPost=q=>{ const fd=new FormData(); fd.append('action','city'); fd.append('q',q); fd.append('sessid',window.BT_SID||'');
      return fetch('/local/ajax/order.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()); };
    const showCities=list=>{ const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
      ul.innerHTML=list.map(c=>`<li role="option" data-code="${esc(c.code)}" data-n="${esc(c.n)}">${esc(c.n)}${c.r?`<small>${esc(c.r)}</small>`:''}</li>`).join('');
      ul.classList.toggle('open',list.length>0); ci.setAttribute('aria-expanded',list.length>0); };
    const open=a=>{
      fill(f,{id:a.id||0,tag:a.tag,loc:a.loc,city:a.city,street:a.street,flat:a.flat,entr:a.entr,who:a.who,tel:a.tel});
      f.elements.main.checked=!!a.main;
      am.querySelector('#adrTitle').textContent=a.id?'Изменить адрес':'Новый адрес';
      am.classList.add('open');
      if(window.BT_YMAPS_KEY&&!mapOn){ mapOn=true; am.classList.add('has-map'); box.hidden=false;
        BT_mapPicker(box,r=>{ if(r.street){ f.elements.street.value=r.street; fieldErr(f.elements.street,''); }
          if(r.city&&r.city!==ci.value) cityPost(r.city).then(x=>{ const c=(x.list||[])[0]; if(c){ ci.value=c.n; f.elements.loc.value=c.code; fieldErr(ci,''); } }); }); }
      if(innerWidth>768) setTimeout(()=>f.elements.tag.focus(),60);
    };
    document.getElementById('addrAdd').addEventListener('click',()=>open({loc:D.loc,city:D.city,who:D.who}));
    am.addEventListener('click',e=>{ if(e.target.closest('[data-close]')) am.classList.remove('open'); });
    document.addEventListener('click',e=>{ const b=e.target.closest('[data-addr-edit],[data-addr-del],[data-addr-main]'); if(!b) return;
      const a=JSON.parse(b.closest('[data-addr]').dataset.addr);
      if(b.matches('[data-addr-edit]')) return open(a);
      const del=b.matches('[data-addr-del]'); b.disabled=true;
      accPost({action:del?'addr_del':'addr_main',id:a.id}).then(r=>r.ok?reloadWith(del?'Адрес удалён':'Адрес сделан основным'):BT_toast(r.message||'Не получилось'))
        .finally(()=>b.disabled=false);
    });
    ci.addEventListener('input',()=>{ f.elements.loc.value=''; clearTimeout(cityT); const v=ci.value.trim();
      if(v.length<2){ showCities([]); return; }
      const n=++cityN; cityT=setTimeout(()=>cityPost(v).then(r=>{ if(n===cityN&&document.activeElement===ci) showCities(r.list||[]); }),200); });
    ul.addEventListener('mousedown',e=>e.preventDefault());
    ul.addEventListener('click',e=>{ const li=e.target.closest('li'); if(!li) return;
      ci.value=li.dataset.n; f.elements.loc.value=li.dataset.code; showCities([]); fieldErr(ci,''); });
    // ввели не до конца и ушли — первое совпадение по началу названия, мусор не подставляем
    const lt=v=>String(v||'').toLowerCase().replace(/ё/g,'е').replace(/[^a-zа-я]/g,'');
    const autoCity=()=>{ const v=ci.value.trim(); if(!v||f.elements.loc.value) return; const n=++cityN; clearTimeout(cityT);
      cityPost(v).then(r=>{ if(n!==cityN||ci.value.trim()!==v||f.elements.loc.value) return;
        const c=(r.list||[]).find(c=>lt(c.n).startsWith(lt(v)));
        if(c){ ci.value=c.n; f.elements.loc.value=c.code; fieldErr(ci,''); } else fieldErr(ci,'Выберите город из списка'); }); };
    ci.addEventListener('blur',()=>{ showCities([]); autoCity(); });
    ci.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); const li=ul.classList.contains('open')&&ul.querySelector('li'); li?li.click():autoCity(); } if(e.key==='Escape') showCities([]); });
    BT_addrSuggest(f.elements.street,{city:()=>f.elements.loc.value?ci.value.trim():''});
    accForm(f,'addr_save',r=>reloadWith(f.elements.id.value>0?'Адрес сохранён':'Адрес добавлен'));
  }

  /* заказы: фильтр по статусу и повтор заказа — позиции уходят в корзину Битрикса */
  document.querySelectorAll('[data-ftabs]').forEach(t=>t.addEventListener('click',e=>{ const b=e.target.closest('[data-f]'); if(!b) return;
    t.querySelectorAll('[data-f]').forEach(x=>x.setAttribute('aria-pressed',x===b));
    document.querySelectorAll('.ord[data-g]').forEach(o=>o.hidden=!!b.dataset.f&&o.dataset.g!==b.dataset.f); }));
  document.addEventListener('click',e=>{ const b=e.target.closest('[data-reorder]'); if(!b||b.disabled) return; b.disabled=true;
    accPost({action:'reorder',id:b.dataset.reorder}).then(r=>{
      if(!r.ok){ BT_toast(r.message||'Не получилось повторить заказ'); return; }
      Object.keys(BT_CART).forEach(k=>delete BT_CART[k]); Object.assign(BT_CART,r.items); cartSave();
      window.BT_BASKET={items:r.items,sum:r.sum}; BT_cartUpdate(true);
      BT_toast(r.added?'Товары из заказа в корзине'+(r.skipped?' (кроме '+r.skipped+' — их нет в каталоге)':'')+' · <a href="/personal/cart/">Оформить</a>':'Этих товаров больше нет в каталоге');
    }).catch(()=>BT_toast('Нет связи с сервером — попробуйте ещё раз')).finally(()=>b.disabled=false);
  });

});
})();

/* ---------- кастомный выпадающий список: нативный <select> остаётся источником значения ---------- */
(function(){
  const CH='<svg aria-hidden="true" focusable="false" viewBox="0 0 12 8"><path d="M1 1.5 6 6.5l5-5"/></svg>';
  let openSel=null;
  function close(sel){ if(!sel) return; sel.classList.remove('is-open');
    sel.querySelector('.sel__btn').setAttribute('aria-expanded','false'); if(openSel===sel) openSel=null; }
  function build(native){
    if(native.multiple||native.dataset.native!=null||native.closest('.sel')) return;
    const sel=document.createElement('div');
    sel.className='sel'+(native.classList.contains('sel-inline')?'':(native.closest('.field')?' sel--block':''));
    native.parentNode.insertBefore(sel,native);
    sel.appendChild(native); native.classList.add('sel__native'); native.tabIndex=-1;
    const id='sel'+Math.random().toString(36).slice(2,8);
    const btn=document.createElement('button');
    btn.type='button'; btn.className='sel__btn'; btn.id=id+'b';
    btn.setAttribute('aria-haspopup','listbox'); btn.setAttribute('aria-expanded','false');
    btn.setAttribute('aria-controls',id);
    const lb=native.closest('.field')&&native.closest('.field').querySelector('label');
    if(lb) btn.setAttribute('aria-label',lb.textContent.replace(/\*/g,'').trim());
    btn.innerHTML='<span></span>'+CH;
    const list=document.createElement('div');
    list.className='sel__list'; list.id=id; list.setAttribute('role','listbox'); list.setAttribute('tabindex','-1');
    sel.append(btn,list);

    const opts=[];
    [...native.children].forEach(node=>{
      if(node.tagName==='OPTGROUP'){
        const g=document.createElement('div'); g.className='sel__grp'; g.textContent=node.label; list.appendChild(g);
        [...node.children].forEach(o=>opts.push(add(o)));
      } else opts.push(add(node));
    });
    function add(o){
      const d=document.createElement('div');
      d.className='sel__opt'; d.setAttribute('role','option'); d.textContent=o.textContent;
      d.setAttribute('aria-selected',o.selected?'true':'false');
      if(o.disabled) d.setAttribute('aria-disabled','true');
      d.addEventListener('click',()=>{ if(o.disabled) return; pick(o); });
      list.appendChild(d); d._o=o; return d;
    }
    let cur=-1;
    const paint=()=>{ const o=native.options[native.selectedIndex];
      btn.querySelector('span').textContent=o?o.textContent:'';
      opts.forEach(d=>d.setAttribute('aria-selected',d._o.selected?'true':'false')); };
    function mark(i){ opts.forEach(d=>d.classList.remove('is-cur'));
      if(i>=0&&opts[i]){ opts[i].classList.add('is-cur'); opts[i].scrollIntoView({block:'nearest'}); } cur=i; }
    function pick(o){ if(native.value!==o.value){ native.value=o.value;
        native.dispatchEvent(new Event('input',{bubbles:true}));
        native.dispatchEvent(new Event('change',{bubbles:true})); }
      paint(); close(sel); btn.focus(); }
    function open(){
      if(openSel&&openSel!==sel) close(openSel);
      /* вверх, если снизу не помещается; вправо, если упирается в правый край */
      const r=btn.getBoundingClientRect();
      list.classList.toggle('sel__list--up', r.bottom+280>innerHeight && r.top>280);
      list.classList.toggle('sel__list--right', r.left+Math.max(240,r.width)>innerWidth-16);
      sel.classList.add('is-open'); btn.setAttribute('aria-expanded','true'); openSel=sel;
      mark(native.selectedIndex);
    }
    btn.addEventListener('click',()=>sel.classList.contains('is-open')?close(sel):open());
    btn.addEventListener('keydown',e=>{
      const o=sel.classList.contains('is-open');
      if(e.key==='ArrowDown'||e.key==='ArrowUp'){ e.preventDefault();
        if(!o){ open(); return; } mark(Math.min(opts.length-1,Math.max(0,cur+(e.key==='ArrowDown'?1:-1)))); }
      else if(e.key==='Home'&&o){ e.preventDefault(); mark(0); }
      else if(e.key==='End'&&o){ e.preventDefault(); mark(opts.length-1); }
      else if((e.key==='Enter'||e.key===' ')&&o){ e.preventDefault(); if(opts[cur]&&!opts[cur]._o.disabled) pick(opts[cur]._o); }
      else if(e.key==='Enter'||e.key===' '){ e.preventDefault(); open(); }
      else if(e.key==='Escape'&&o){ e.preventDefault(); close(sel); }
      else if(e.key==='Tab'&&o){ close(sel); }
    });
    native.addEventListener('change',paint);
    /* список опций может прийти позже (селект заполняет скрипт страницы) — пересобираем */
    new MutationObserver(()=>{ list.innerHTML=''; opts.length=0;
      [...native.children].forEach(node=>{
        if(node.tagName==='OPTGROUP'){ const g=document.createElement('div'); g.className='sel__grp'; g.textContent=node.label; list.appendChild(g);
          [...node.children].forEach(o=>opts.push(add(o))); }
        else opts.push(add(node));
      }); paint(); }).observe(native,{childList:true});
    /* ошибки валидации подсвечиваем на обёртке */
    new MutationObserver(()=>sel.classList.toggle('is-err',
      !!native.closest('.field')&&native.closest('.field').classList.contains('is-err')))
      .observe(native.closest('.field')||native,{attributes:true,attributeFilter:['class']});
    paint();
  }
  window.BT_selects = root => (root||document).querySelectorAll('select').forEach(build);
  document.addEventListener('click',e=>{ if(openSel&&!e.target.closest('.sel')) close(openSel); });
  document.addEventListener('keydown',e=>{ if(e.key==='Escape'&&openSel){ const b=openSel.querySelector('.sel__btn'); close(openSel); b.focus(); } });
  addEventListener('resize',()=>close(openSel));
  document.addEventListener('DOMContentLoaded',()=>BT_selects());
})();

/* ---------- характеристики для страницы сравнения ---------- */
window.BT_SPEC_GROUPS = {
  coffee:[
    ['Основное',[['Обжарка','roast'],['Состав','mix'],['Вес упаковки','w']]],
    ['Происхождение',[['Страна','country'],['Регион','region'],['Обработка','proc']]],
    ['Вкус',[['Q-оценка','q'],['Дескрипторы','notes']]]
  ],
  tea:[
    ['Основное',[['Вид чая','kind'],['Фасовка','pack'],['Вес упаковки','w']]],
    ['Происхождение',[['Страна','country']]],
    ['Вкус и действие',[['Вкус','taste'],['Действие','effect']]]
  ],
  machines:[
    ['Основное',[['Чашек в день','cups'],['Габариты','dims'],['Экран','screen']]]
  ],
  acc:[
    ['Основное',[['Вес/объём','w']]]
  ]
};
// характеристики для сравнения отдаёт сервер на странице /magazin/compare/
window.BT_SPECS = window.BT_SPECS || {};
window.BT_cmpCat = id => BT_PRODUCTS.coffee.some(x=>x.id===id)?'coffee'
  : BT_PRODUCTS.tea.some(x=>x.id===id)?'tea'
  : BT_PRODUCTS.machines.some(x=>x.id===id)?'machines'
  : (BT_PRODUCTS.acc||[]).some(x=>x.id===id)?'acc':'other';
window.BT_CAT_NAMES = {coffee:'Кофе',tea:'Чай',machines:'Кофемашины',acc:'Аксессуары',other:'Прочее'};

/* ---------- появление блоков при скролле (только там, где расставлены data-rv) ---------- */
(function(){
  function init(){
    const els=document.querySelectorAll('[data-rv]'); if(!els.length) return;
    if(matchMedia('(prefers-reduced-motion:reduce)').matches||!('IntersectionObserver' in window)){
      els.forEach(e=>e.classList.add('rv-in')); return; }
    const io=new IntersectionObserver(es=>es.forEach(en=>{
      if(!en.isIntersecting) return; io.unobserve(en.target);
      en.target.classList.add('rv-in');
      setTimeout(()=>en.target.classList.add('rv-done'),900);
    }),{rootMargin:'0px 0px -12% 0px',threshold:.12});
    els.forEach(e=>io.observe(e));
  }
  document.addEventListener('DOMContentLoaded',init);
  window.BT_reveal=init;
})();

/* ---------- слайдеры: единственная библиотека на проекте — Swiper ---------- */
window.BT_sliderBtns = () => `
  <button class="sw-btn sw-btn--p" type="button" aria-label="Назад"><svg viewBox="0 0 24 24"><path d="m15 5-7 7 7 7"/></svg></button>
  <button class="sw-btn sw-btn--n" type="button" aria-label="Вперёд"><svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg></button>`;
/* превращает контейнер с карточками в слайдер: .grid → .swiper-wrapper, дети → .swiper-slide */
window.BT_slider = (box, opts) => {
  if(!box || !window.Swiper || box.dataset.sw) return null;
  const kids=[...box.children]; if(!kids.length) return null;
  const min=(opts&&opts.min)||5;                 /* меньше — оставляем обычной сеткой */
  if(kids.length<min) return null;
  box.dataset.sw='1';
  const wrap=document.createElement('div'); wrap.className='sw';
  const sw=document.createElement('div'); sw.className='swiper';
  const wr=document.createElement('div'); wr.className='swiper-wrapper';
  box.parentNode.insertBefore(wrap,box);
  kids.forEach(k=>{const sl=document.createElement('div');sl.className='swiper-slide';sl.appendChild(k);wr.appendChild(sl);});
  box.remove(); sw.appendChild(wr); wrap.appendChild(sw); wrap.insertAdjacentHTML('beforeend',BT_sliderBtns());
  return new Swiper(sw,Object.assign({
    slidesPerView:1.15, spaceBetween:14, watchOverflow:true, grabCursor:true,
    a11y:{prevSlideMessage:'Назад',nextSlideMessage:'Вперёд'},
    navigation:{prevEl:wrap.querySelector('.sw-btn--p'),nextEl:wrap.querySelector('.sw-btn--n')},
    breakpoints:{560:{slidesPerView:2,spaceBetween:14},900:{slidesPerView:3,spaceBetween:16},1200:{slidesPerView:4,spaceBetween:18}}
  },(opts&&opts.swiper)||{}));
};

/* ---------- Яндекс Карты ----------
   Интерактивная карта (клик по точке + определение адреса) работает только с API-ключом:
   ключ бесплатный, лимит Геокодера — 1000 запросов в сутки. Вписать ключ сюда:            */
window.BT_YMAPS_KEY = '';           /* ← сюда ключ из кабинета разработчика Яндекса */
window.BT_CO_COORDS = [60.533999,56.78314];   /* ул. Колокольная, 31А — точка Яндекса */

/* Статичная карта-виджет: работает БЕЗ ключа (конструктор карт Яндекса) */
window.BT_MAP_QUERY = 'Екатеринбург, улица Колокольная, 31А';
/* Проекция Яндекса — Меркатор на эллипсоиде WGS84: пиксели мира на масштабе z <-> долгота/широта */
const BT_merc = {
  e: 0.0818191908426,
  toPx(lon, lat, z){ const S = 256*2**z, f = lat*Math.PI/180, es = this.e*Math.sin(f);
    return [(lon+180)/360*S, S/2 - S/(2*Math.PI)*Math.log(Math.tan(Math.PI/4+f/2)*((1-es)/(1+es))**(this.e/2))]; },
  toLL(x, y, z){ const S = 256*2**z, t = Math.exp((S/2-y)*2*Math.PI/S); let f = 2*Math.atan(t)-Math.PI/2;
    for(let i=0;i<6;i++){ const es=this.e*Math.sin(f); f = 2*Math.atan(t*((1+es)/(1-es))**(this.e/2))-Math.PI/2; }
    return [x/S*360-180, f*180/Math.PI]; }
};
window.BT_mapWidget = (el, opts) => {
  if(!el) return;
  /* статичная карта Яндекса без ключа, рекламы и кнопок виджета. Одна картинка — максимум 650×450,
     поэтому большой блок собираем из кусков внахлёст: следующий кусок закрывает логотип предыдущего, виден один — в правом нижнем углу */
  const o = Object.assign({c:BT_CO_COORDS, z:17}, opts||{});
  const W = Math.round(el.clientWidth||650), H = Math.round(el.clientHeight||420);
  const cols = W <= 650 ? 1 : Math.max(2, Math.ceil((W-210)/(650-210))), rows = H <= 450 ? 1 : Math.ceil((H-30)/(450-30));
  const tw = cols > 1 ? 650 : W, th = rows > 1 ? 450 : H;
  const [cx, cy] = BT_merc.toPx(o.c[0], o.c[1], o.z);
  const box = document.createElement('div'); box.className = 'map__tiles';
  let left = cols*rows;
  for(let r=0;r<rows;r++) for(let c=0;c<cols;c++){
    const x0 = cols > 1 ? Math.round(c*(W-tw)/(cols-1)) : 0, y0 = rows > 1 ? Math.round(r*(H-th)/(rows-1)) : 0;
    const [lon, lat] = BT_merc.toLL(cx + x0 + tw/2 - W/2, cy + y0 + th/2 - H/2, o.z);
    const img = new Image(); img.alt = ''; img.decoding = 'async'; img.loading = 'lazy';
    img.style.cssText = `left:${x0}px;top:${y0}px;width:${tw}px;height:${th}px`;
    img.onload = img.onerror = () => { if(--left === 0) el.classList.add('is-map'); };
    img.src = `https://static-maps.yandex.ru/1.x/?ll=${lon.toFixed(6)},${lat.toFixed(6)}&z=${o.z}&size=${tw},${th}&l=map&lang=ru_RU`;
    box.appendChild(img);
  }
  const lat = o.c[1], lon = o.c[0];
  box.setAttribute('role','img'); box.setAttribute('aria-label','Карта: '+BT_MAP_QUERY);
  el.prepend(box);
  el.insertAdjacentHTML('beforeend', `<span class="map__pin"><svg viewBox="0 0 24 24" width="40" height="40" aria-hidden="true"><path fill="#0E0E0C" d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Z"/><circle cx="12" cy="9" r="2.8" fill="#D7E85C"/></svg></span>
    <span class="map__btns"><a class="map__btn" href="https://yandex.ru/maps/?pt=${lon},${lat}&z=17&l=map" target="_blank" rel="noopener">Открыть в Яндекс Картах</a><a class="map__btn map__btn--lime" href="https://yandex.ru/maps/?rtext=~${lat},${lon}&rtt=auto" target="_blank" rel="noopener">Маршрут</a></span>`);
  return box;
};

/* Интерактивная карта с выбором точки. Без ключа возвращает null — вызывающий код
   оставляет схематичную подложку и ручной ввод адреса. */
window.BT_mapPicker = (el, onPick, opts) => {
  if(!el || !BT_YMAPS_KEY) return null;
  const load = () => new Promise((res,rej)=>{
    if(window.ymaps3) return res(window.ymaps3);
    const s=document.createElement('script');
    s.src=`https://api-maps.yandex.ru/v3/?apikey=${BT_YMAPS_KEY}&lang=ru_RU`;
    s.onload=()=>res(window.ymaps3); s.onerror=rej; document.head.appendChild(s);
  });
  return load().then(async ymaps3=>{
    await ymaps3.ready;
    const {YMap,YMapDefaultSchemeLayer,YMapDefaultFeaturesLayer,YMapMarker,YMapListener}=ymaps3;
    const center=(opts&&opts.center)||BT_CO_COORDS;
    const map=new YMap(el,{location:{center,zoom:(opts&&opts.zoom)||14}});
    map.addChild(new YMapDefaultSchemeLayer()); map.addChild(new YMapDefaultFeaturesLayer());
    const pin=document.createElement('div');
    pin.innerHTML='<svg viewBox="0 0 24 24" width="30" height="30" fill="#0E0E0C"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Z"/><circle cx="12" cy="9" r="2.8" fill="#D7E85C"/></svg>';
    pin.style.cssText='transform:translate(-50%,-100%)';
    let marker=null;
    map.addChild(new YMapListener({layer:'any', onClick:async (_,e)=>{
      const c=e.coordinates;
      if(!marker){ marker=new YMapMarker({coordinates:c},pin); map.addChild(marker); }
      else marker.update({coordinates:c});
      /* обратное геокодирование: адрес по координатам */
      try{
        const r=await fetch(`https://geocode-maps.yandex.ru/1.x/?apikey=${BT_YMAPS_KEY}&format=json&lang=ru_RU&geocode=${c[0]},${c[1]}`);
        const j=await r.json();
        const g=j.response.GeoObjectCollection.featureMember[0]?.GeoObject;
        const a=g?.metaDataProperty?.GeocoderMetaData?.Address||{};
        const comp=(a.Components||[]).reduce((m,x)=>((m[x.kind]=x.name),m),{});
        onPick&&onPick({
          city: comp.locality||'',
          street: [comp.street,comp.house].filter(Boolean).join(', '),
          full: a.formatted||'', coords:c
        });
      }catch(err){ onPick&&onPick({city:'',street:'',full:'',coords:c}); }
    }}));
    return map;
  }).catch(()=>null);
};

/* Страница результатов поиска: свой крестик очистки вместо системного */
document.addEventListener('click',e=>{const b=e.target.closest('.spage__clr'); if(!b) return;
  const f=b.form; f.q.value=''; f.classList.remove('has-q'); f.q.focus();});
document.addEventListener('input',e=>{const f=e.target.closest&&e.target.closest('.spage__f'); if(f) f.classList.toggle('has-q',!!e.target.value);});
/* вкладки результатов поиска: подменяем блок результатов без перезагрузки страницы */
(()=>{
  const load=(url,push)=>{const box=document.getElementById('spRes'); if(!box) return location.assign(url);
    box.classList.add('is-loading');
    fetch(url,{credentials:'same-origin'}).then(r=>r.text()).then(html=>{
      const n=new DOMParser().parseFromString(html,'text/html').getElementById('spRes');
      if(!n) return location.assign(url);
      box.replaceWith(n); window.BT_phFit&&BT_phFit(n);
      if(push) history.pushState({sp:1},'',url);
      Object.keys(BT_CART).forEach(id=>document.dispatchEvent(new CustomEvent('bt:cart',{detail:{id,q:BT_CART[id]}})));
    }).catch(()=>location.assign(url));
  };
  document.addEventListener('click',e=>{const a=e.target.closest('.spage__tab'); if(!a||e.ctrlKey||e.metaKey||e.shiftKey) return;
    e.preventDefault(); if(!a.hasAttribute('aria-current')) load(a.href,true);});
  addEventListener('popstate',()=>{ if(document.getElementById('spRes')) load(location.href,false); });
})();
/* «Скопировать» (реквизиты и т.п.): текст из data-copy в буфер обмена */
document.addEventListener('click',e=>{const b=e.target.closest('[data-copy]'); if(!b) return;
  const done=()=>window.BT_toast&&BT_toast('Скопировано в буфер обмена');
  if(navigator.clipboard) navigator.clipboard.writeText(b.dataset.copy).then(done).catch(()=>{});
  else { const t=document.createElement('textarea'); t.value=b.dataset.copy; document.body.appendChild(t); t.select(); try{document.execCommand('copy');done();}catch(err){} t.remove(); }});

/* Окно подтверждения на стилях модалок: BT_confirm({title,text,ok,cancel}) → Promise<boolean>. Esc, фон и «×» — отмена */
window.BT_confirm = o => new Promise(res => {
  let m=document.getElementById('btConfirm');
  if(!m){ document.body.insertAdjacentHTML('beforeend',`<div class="modal modal--confirm" id="btConfirm" role="alertdialog" aria-modal="true" aria-labelledby="btConfirmT" aria-describedby="btConfirmD">
    <div class="modal__bg" data-cf="0"></div><div class="modal__p"><button class="modal__x" type="button" data-cf="0" aria-label="Закрыть">×</button>
    <h3 class="display" id="btConfirmT"></h3><p class="muted" id="btConfirmD"></p>
    <div class="modal__btns"><button class="btn btn--line" type="button" data-cf="0"></button><button class="btn btn--dark" type="button" data-cf="1"></button></div></div></div>`);
    m=document.getElementById('btConfirm'); }
  const last=document.activeElement;
  m.querySelector('#btConfirmT').textContent=o.title||'Вы уверены?';
  m.querySelector('#btConfirmD').innerHTML=o.text||'';
  m.querySelector('.modal__btns [data-cf="0"]').textContent=o.cancel||'Отмена';
  m.querySelector('.modal__btns [data-cf="1"]').textContent=o.ok||'Да';
  const done=v=>{ m.classList.remove('open'); m.removeEventListener('click',onClick); removeEventListener('keydown',onKey,true); if(last&&last.focus) last.focus(); res(v); };
  const onClick=e=>{ const b=e.target.closest('[data-cf]'); if(b) done(b.dataset.cf==='1'); };
  const onKey=e=>{ if(e.key==='Escape'){ e.stopPropagation(); done(false); } };
  m.addEventListener('click',onClick); addEventListener('keydown',onKey,true);
  m.classList.add('open'); setTimeout(()=>m.querySelector('.modal__btns [data-cf="1"]').focus(),30);
});

/* Подсказки адреса DaData: улица и дом в городе (opt.city) или полный адрес (opt.full). Ввели не до конца и ушли из поля — подставляем лучшее совпадение */
window.BT_addrSuggest=(inp,opt={})=>{
  if(!inp||inp.dataset.sugg) return; inp.dataset.sugg=1;
  const f=inp.closest('.field'); f.classList.add('city','street');
  Object.entries({autocomplete:'new-password',spellcheck:'false',role:'combobox','aria-autocomplete':'list','aria-expanded':'false'}).forEach(([k,v])=>inp.setAttribute(k,v));
  const ul=document.createElement('ul'); ul.setAttribute('role','listbox'); inp.after(ul);
  const esc=v=>String(v??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const words=v=>String(v||'').toLowerCase().replace(/ё/g,'е').replace(/\d.*$/,'').split(/[^a-zа-я]+/).filter(w=>w.length>1&&!/^(ул|улица|д|дом|г|город|пр|кв|обл|р-н)$/.test(w));
  const flat=v=>String(v||'').toLowerCase().replace(/ё/g,'е').replace(/[^a-zа-я]/g,'');
  const req=q=>{const fd=new FormData();fd.append('action','street');fd.append('q',q);fd.append('sessid',window.BT_SID||'');
    opt.full?fd.append('full','1'):fd.append('city',opt.city?opt.city():'');
    return fetch('/local/ajax/order.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json());};
  const show=list=>{ul.innerHTML=list.map(s=>`<li role="option" data-v="${esc(s.v)}" data-h="${s.house?1:0}">${esc(s.v)}${s.r?`<small>${esc(s.r)}</small>`:''}</li>`).join('');
    ul.classList.toggle('open',list.length>0);inp.setAttribute('aria-expanded',list.length>0);};
  const err=m=>window.BT_fieldErr&&BT_fieldErr(inp,m);
  const changed=()=>inp.dispatchEvent(new Event('change',{bubbles:true}));
  let t=0,n=0;
  inp.addEventListener('input',()=>{const v=inp.value.trim();clearTimeout(t);if(v.length<2){show([]);return;}
    const k=++n;t=setTimeout(()=>req(v).then(r=>{if(k===n&&document.activeElement===inp)show(r.list||[]);}),250);});
  ul.addEventListener('mousedown',e=>e.preventDefault());
  ul.addEventListener('click',e=>{const li=e.target.closest('li');if(!li)return;
    inp.value=li.dataset.v+(li.dataset.h==='1'||opt.full?'':', д ');show([]);err('');inp.focus();changed();
    if(li.dataset.h!=='1'&&!opt.full)inp.dispatchEvent(new Event('input'));});
  const auto=()=>{const v=inp.value.trim();if(v.length<2)return;const k=++n;clearTimeout(t);
    req(v).then(r=>{if(k!==n||inp.value.trim()!==v)return;
      // слова введённого должны встречаться в подсказке в том же порядке: «москва тверская» ≠ «Тверская обл, деревня Москва»
      const num=v.match(/\d.*$/),w=words(v),fits=x=>{let i=0;return w.every(y=>{const k=flat(x).indexOf(y,i);if(k<0)return false;i=k+y.length;return true;});};
      const s=w.length&&(r.list||[]).find(x=>fits(x.v));
      if(!s)return;
      const same=s.house&&num&&s.v.replace(/\D/g,'').endsWith(num[0].replace(/\D/g,''));
      inp.value=same?s.v:opt.full?(num?v:s.v):(s.s||v.replace(/[\s,]*\d.*$/,''))+(num?', д '+num[0].replace(/^(д|дом)[\s.]*/i,''):'');
      show([]);if(num)err('');changed();});};
  inp.addEventListener('blur',()=>{show([]);auto();});
  inp.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();const li=ul.classList.contains('open')&&ul.querySelector('li');li?li.click():auto();}
    if(e.key==='Escape'&&ul.classList.contains('open')){e.preventDefault();show([]);}});
};
document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('input[name=company_adr]').forEach(i=>BT_addrSuggest(i,{full:true})));
