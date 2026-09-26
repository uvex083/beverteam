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
/* Провайдеры входа. Монограммы в фирменных цветах — в продакшене ставятся официальные SVG
   из брендбуков Яндекс ID / VK ID / T-Bank ID / Сбер ID / Альфа ID. */
const IDP=[
  {id:'ya',   t:'Яндекс ID',  m:'Я', cls:'i-ya'},
  {id:'vk',   t:'VK ID',      m:'VK',cls:'i-vk'},
  {id:'tb',   t:'T-Bank ID',  m:'Т', cls:'i-tb'},
  {id:'sber', t:'Сбер ID',    m:'С', cls:'i-sber'},
  {id:'alfa', t:'Альфа ID',   m:'А', cls:'i-alfa'},
  {id:'mail', t:'По почте',   m:'',  cls:'i-mail', ic:'mail'}
];
window.BT_IDP=IDP;

/* ---------- Журнал: новости и статьи в одном инфоблоке, рубрика решает раздел ---------- */
// материалы журнала отдаёт сервер (header.php → bt_posts)
window.BT_POSTS=window.BT_POSTS||[];
window.BT_postUrl = p => p.url||'/news/'+p.id+'/';

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
    <div class="ncard__b"><div class="ncard__m"><span class="tag">${p.cat}</span><time datetime="${p.d}">${BT_postDate(p.d)}</time>${p.kind==='news'?'<span class="tag tag--n">Новость</span>':''}</div>
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
window.BT_idpHtml = (label) => `<div class="idp--row">${label?`<b>${label}</b>`:''}<div class="idp">${IDP.map(p=>
  `<button type="button" class="${p.cls}" data-idp="${p.id}" title="${p.t}" aria-label="Войти через ${p.t}">${p.ic?I[p.ic]:p.m}</button>`).join('')}</div></div>`;

/* Текущий пользователь прототипа */
let USER=null; try{ USER=JSON.parse(localStorage.getItem('bt_user')||'null'); }catch(e){}
window.BT_user = () => USER;
window.BT_login = u => { USER=u; try{localStorage.setItem('bt_user',JSON.stringify(u));}catch(e){}
  document.dispatchEvent(new CustomEvent('bt:auth',{detail:u})); BT_authUpdate(); };
window.BT_logout = () => { USER=null; try{localStorage.removeItem('bt_user');}catch(e){}
  document.dispatchEvent(new CustomEvent('bt:auth',{detail:null})); BT_authUpdate(); };
window.BT_accNav = cur => {
  const u=USER||{name:'Гость'};
  const it=[['profile','/personal/','Профиль',''],['orders','/personal/orders/','Заказы','<span class="cnt">4</span>'],
    ['sub','/personal/podpiska/','Подписка на кофе','<span class="badge badge--ok">активна</span>'],
    ['addr','/personal/addresses/','Адреса доставки',''],['docs','/personal/docs/','Счета и документы','']];
  return `<aside class="acc-nav">
    <div class="u"><i>${(u.name||'?')[0]}</i><div><b>${(u.name||'Гость').split(' ')[0]}</b><small>${u.email||u.phone||''}</small></div></div>
    ${it.map(x=>`<a href="${x[1]}" class="${x[0]===cur?'cur':''}">${x[2]} ${x[3]}</a>`).join('')}
    <a href="#" onclick="BT_toast('Избранное — в прототипе не реализовано');return false">Избранное</a>
    <a class="out" href="/" onclick="BT_logout()">Выйти</a>
  </aside>`;
};
window.BT_authUpdate = () => {
  const a=document.querySelector('.hact[href="/personal/"]'); if(!a) return;
  const l=a.querySelector('span:not(.cnt)');
  if(USER){ a.classList.add('is-auth'); if(l) l.textContent=(USER.name||'').split(' ')[0]||'Кабинет'; a.title=USER.email||USER.phone||''; }
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
  fd.append('sessid',window.BX&&BX.bitrix_sessid?BX.bitrix_sessid():'');
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
  const c=a.querySelector('.cnt'); if(c) c.textContent=t.n;
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
  {t:'Журнал', h:'/news/', sub:[['Статьи','/news/'],['Новости','/news/'],['Подбор кофе за минуту','/podbor-kofe/']]},
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
      <a class="hact" href="/personal/cart/" aria-label="Корзина">${I.cart}<span class="cnt">${cartCnt}</span><span>Корзина</span></a>
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
    ${c.promo?`<a class="mega__promo" href="${c.promo.h}"><img src="${c.promo.img}" alt=""><b>${c.promo.b}</b><span class="muted" style="font-size:13px">${c.promo.t}</span></a>`:''}`;
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
      <a class="drawer__l" href="/news/">Журнал</a>
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
    <div><h5>Покупателям</h5><ul><li><a href="/oplata-i-dostavka/">Оплата и доставка</a></li><li><a href="/vozvrat-i-obmen/">Возврат и обмен</a></li><li><a href="/politika-konfidencialnosti/">Политика обработки персональных данных</a></li><li><a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a></li><li><a href="/o-kompanii/">О компании</a></li><li><a href="/news/">Журнал</a></li><li><a href="/podbor-kofe/">Подбор кофе</a></li><li><a href="/personal/">Личный кабинет</a></li><li><a href="/sitemap/">Карта сайта</a></li></ul></div>
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

    <!-- шаг 1: выбор способа -->
    <div data-step="pick">
      <h3 class="display" id="authTitle" style="font-size:20px;margin-bottom:6px">Вход и регистрация</h3>
      <p class="muted" style="margin:0 0 18px;font-size:14px">Пароль не нужен: войдите через сервис или получите код.</p>
      ${BT_idpHtml('')}
      <div class="or">или получить код</div>
      <div class="field"><label for="aLogin" id="aLabel">E-mail</label>
        <input id="aLogin" type="email" inputmode="email" autocomplete="email" placeholder="mail@company.ru">
        <span class="hint" id="aHint">Пришлём код на почту — пароль не нужен</span></div>
      <button class="btn btn--block" id="aSend">Продолжить</button>
      <p class="muted" style="margin:14px 0 0;font-size:12px;line-height:1.5">Продолжая, вы принимаете <a class="link" href="/polzovatelskoe-soglashenie/" target="_blank" rel="noopener">пользовательское соглашение</a> и <a class="link" href="/politika-konfidencialnosti/" target="_blank" rel="noopener">политику обработки персональных данных</a>.</p>
    </div>

    <!-- шаг 2: код -->
    <div data-step="code" hidden>
      <button class="authback" id="aBack" type="button">← Изменить</button>
      <h3 class="display" style="font-size:20px;margin-bottom:6px">Введите код</h3>
      <p class="muted" style="margin:0 0 16px;font-size:14px">Отправили на <b id="aTo"></b></p>
      <div class="code4" id="aCode">
        <input inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Цифра 1">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 2">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 3">
        <input inputmode="numeric" maxlength="1" aria-label="Цифра 4">
      </div>
      <p class="resend" id="aResend"></p>
      <p class="muted" style="font-size:12.5px;margin:10px 0 0">Демо: код <b>1234</b></p>
    </div>

    <!-- шаг 3: имя для нового аккаунта -->
    <div data-step="name" hidden>
      <h3 class="display" style="font-size:20px;margin-bottom:6px">Как к вам обращаться</h3>
      <p class="muted" style="margin:0 0 16px;font-size:14px">Подставим в заказы и документы.</p>
      <div class="field"><label for="aName">Имя и фамилия</label><input id="aName" autocomplete="name" placeholder="Сергей Гаврилов"></div>
      <button class="btn btn--block" id="aFinish">Готово</button>
    </div>

    <!-- шаг 4: вошли -->
    <div data-step="done" hidden>
      <div class="authu"><i id="aAv"></i><span><b id="aUName"></b><br><span class="muted" style="font-size:13px" id="aUId"></span></span></div>
      <a class="btn btn--block" href="/personal/">Личный кабинет</a>
      <button class="btn btn--block btn--ghost" style="margin-top:10px" id="aOut">Выйти</button>
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
    text:{test:v=>v.trim().length>0, msg:'Заполните поле'}
  };
  const kindOf = el => {
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
  const dr=document.getElementById('drawer');watch(dr,'[data-close].drawer__x');
  document.getElementById('burger').addEventListener('click',()=>dr.classList.add('open'));
  dr.addEventListener('click',e=>{if(e.target.closest('[data-close]'))dr.classList.remove('open');});
  if(location.hash==='#menu') dr.classList.add('open');

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

  const PAGES=[{t:'Аренда кофемашин',u:'/arenda-kofemashin/',d:'Услуга · от 3 500 ₽ в месяц'},
    {t:'Кофе по подписке',u:'/podpiska/',d:'Услуга · кофемашина бесплатно от 3 кг'},
    {t:'Ремонт и обслуживание кофемашин',u:'/servis/#price',d:'Услуга · диагностика бесплатно'},
    {t:'Оплата и доставка',u:'/oplata-i-dostavka/',d:'Информация'},
    {t:'Журнал',u:'/news/',d:'Статьи и новости'},
    {t:'Контакты',u:'/kontakty/',d:'Екатеринбург, ул. Колокольная, 31А'}];

  function paintResults(q){
    const ql=q.toLowerCase();
    const prods=Object.values(BT_PRODUCTS).flat()
      .filter(m=>(m.n+' '+m.par).toLowerCase().includes(ql)).slice(0,6);
    const pages=PAGES.filter(p=>(p.t+' '+p.d).toLowerCase().includes(ql)).slice(0,4);
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
  sq && sq.addEventListener('keydown',e=>{ if(e.key==='Enter'&&sq.value.trim().length>1){ pushRecent(sq.value.trim()); }});
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

  /* ---------- авторизация: провайдеры + код, единый поток ---------- */
  const au=document.getElementById('auth');watch(au,'input,button');
  const step = n => { au.querySelectorAll('[data-step]').forEach(x=>x.hidden=x.dataset.step!==n);
    const f=au.querySelector(`[data-step="${n}"] input,[data-step="${n}"] .btn`); if(f) setTimeout(()=>f.focus(),40); };
  const KNOWN={'sergey@example.ru':'Сергей Гаврилов','test@beverteam.ru':'Василий Чичиланов','+79043841388':'Василий Чичиланов'};
  let pending=null, timer=null;

  window.BT_auth=()=>{ au.classList.add('open'); step(USER?'done':'pick'); if(USER) fillDone(); };
  au.addEventListener('click',e=>{if(e.target.closest('[data-close]'))au.classList.remove('open');});

  function fillDone(){ if(!USER)return;
    au.querySelector('#aAv').textContent=(USER.name||'?').trim()[0].toUpperCase();
    au.querySelector('#aUName').textContent=USER.name||'Без имени';
    au.querySelector('#aUId').textContent=(USER.email||USER.phone||'')+(USER.via?' · через '+USER.via:''); }

  /* нормализация логина: телефон или почта */
  const norm = v => { v=v.trim(); if(v.includes('@')) return {type:'mail',id:v.toLowerCase()};
    const d=v.replace(/\D/g,''); if(d.length>=10) return {type:'tel',id:'+7'+d.slice(-10)}; return null; };

  function startCode(id,via){
    pending={id,via};
    au.querySelector('#aTo').textContent=id;
    au.querySelectorAll('#aCode input').forEach(i=>i.value='');
    step('code'); tick(59);
  }
  function tick(sec){ const el=au.querySelector('#aResend'); clearInterval(timer);
    const run=()=>{ if(sec<=0){ clearInterval(timer); el.innerHTML='<a class="link" href="#" id="aAgain">Отправить код ещё раз</a>';
        el.querySelector('#aAgain').onclick=ev=>{ev.preventDefault();tick(59);BT_toast('Код отправлен повторно');}; return; }
      el.textContent='Отправить код ещё раз можно через '+sec+' с'; sec--; };
    run(); timer=setInterval(run,1000); }

  /* поле само понимает, что вводят: цифры → телефон с маской +7, иначе e-mail */
  (function(){
    const inp=au.querySelector('#aLogin'), lab=au.querySelector('#aLabel'), hint=au.querySelector('#aHint');
    const telFmt = v => { let d=v.replace(/\D/g,''); if(!d) return '';
      if(d[0]==='8') d='7'+d.slice(1); if(d[0]!=='7') d='7'+d; d=d.slice(0,11);
      let r='+7'; if(d.length>1)r+=' ('+d.slice(1,4); if(d.length>=5)r+=') '+d.slice(4,7);
      if(d.length>=8)r+='-'+d.slice(7,9); if(d.length>=10)r+='-'+d.slice(9,11); return r; };
    const setMode = m => {
      if(inp.dataset.mode===m) return; inp.dataset.mode=m;
      if(m==='tel'){ inp.type='tel'; inp.inputMode='tel'; inp.autocomplete='tel';
        lab.textContent='Телефон'; hint.textContent='Пришлём код в SMS'; }
      else { inp.type='email'; inp.inputMode='email'; inp.autocomplete='email';
        lab.textContent='E-mail'; hint.textContent='Пришлём код на почту — пароль не нужен'; }
    };
    inp.addEventListener('input',()=>{
      const v=inp.value;
      if(/^[\d+ ()-]*$/.test(v) && /\d/.test(v)){ setMode('tel'); const f=telFmt(v); if(f!==v) inp.value=f; }
      else if(!v){ setMode('mail'); }
      else { setMode('mail'); }
    });
    inp.addEventListener('blur',()=>{ if(inp.dataset.mode==='tel' && inp.value.replace(/\D/g,'').length<=1) inp.value=''; });
    setMode('mail');
  })();

  au.querySelector('#aSend').addEventListener('click',()=>{
    const inp=au.querySelector('#aLogin');
    const n=norm(inp.value);
    if(!n){ inp.closest('.field').classList.add('is-err'); if(!inp.closest('.field').querySelector('.err'))
        inp.closest('.field').insertAdjacentHTML('beforeend','<span class="err">Введите e-mail или телефон полностью</span>'); inp.focus(); return; }
    inp.closest('.field').classList.remove('is-err');
    startCode(n.id,null);
  });
  au.querySelector('#aBack').addEventListener('click',()=>{clearInterval(timer);step('pick');});

  /* вход через провайдера: в прототипе сразу возвращает профиль, в бою — OAuth-редирект */
  au.addEventListener('click',e=>{ const b=e.target.closest('[data-idp]'); if(!b) return;
    const p=IDP.find(x=>x.id===b.dataset.idp);
    if(p.id==='mail'){ const inp=au.querySelector('#aLogin'); inp.focus(); return; }
    BT_toast('Переход в '+p.t+'…');
    setTimeout(()=>{ BT_login({name:'Сергей Гаврилов',email:'uvex083@yandex.ru',via:p.t}); fillDone(); step('done');
      BT_toast('Вы вошли через '+p.t); },500);
  });

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
    const code=cells.map(c=>c.value).join('');
    if(code!=='1234'){ const w=au.querySelector('#aCode'); w.classList.add('is-err');
      setTimeout(()=>{w.classList.remove('is-err');cells.forEach(c=>c.value='');cells[0].focus();},420); return; }
    clearInterval(timer);
    const known=KNOWN[pending.id];
    if(known){ BT_login({name:known, [pending.id.includes('@')?'email':'phone']:pending.id}); fillDone(); step('done'); BT_toast('С возвращением, '+known.split(' ')[0]); }
    else step('name');
  }
  au.querySelector('#aFinish').addEventListener('click',()=>{
    const n=au.querySelector('#aName');
    if(n.value.trim().length<2){ n.closest('.field').classList.add('is-err'); n.focus(); return; }
    BT_login({name:n.value.trim(), [pending.id.includes('@')?'email':'phone']:pending.id});
    fillDone(); step('done'); BT_toast('Аккаунт создан');
  });
  au.querySelector('#aOut').addEventListener('click',()=>{BT_logout();step('pick');BT_toast('Вы вышли');});

  BT_authUpdate();
  if(location.hash==='#auth') BT_auth();
  document.querySelectorAll('[data-auth]').forEach(el=>el.addEventListener('click',e=>{e.preventDefault();BT_auth();}));
  /* «Кабинет» в шапке: гостя ведём в модалку, авторизованного — на страницу */
  document.querySelectorAll('.hact[href="/personal/"],.drawer__l[href="/personal/"]').forEach(a=>
    a.addEventListener('click',e=>{ if(!USER){ e.preventDefault(); BT_auth(); } }));

  /* toast + add to cart */
  const t=document.getElementById('toast');let tm;
  window.BT_toast=msg=>{t.innerHTML=msg;t.classList.add('show');clearTimeout(tm);tm=setTimeout(()=>t.classList.remove('show'),3200);};
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

  /* ===== Доработки SEO: canonical + микроразметка (Списко доработок, п.1 и п.7) ===== */
  const SITE='https://beverteam.ru/';
  const addLd=o=>{const t=document.createElement('script');t.type='application/ld+json';t.textContent=JSON.stringify(o);document.head.appendChild(t);};

  /* canonical — в прототипе проставляется скриптом; на Битриксе выводится в <head> шаблона */
  if(!document.querySelector('link[rel="canonical"]')){
    const l=document.createElement('link');l.rel='canonical';
    l.href=SITE+location.pathname.split('/').pop().replace(/index\.html$/,'');
    document.head.appendChild(l);
  }

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
    const sec=b.closest('section'); b.parentNode.querySelectorAll('[data-tab]').forEach(x=>x.setAttribute('aria-selected',x===b));
    sec.querySelectorAll('[data-tabpane]').forEach(p=>p.hidden=p.dataset.tabpane!==b.dataset.tab);
    BT_cmpUpdate(); BT_favUpdate();
  }));
  document.querySelectorAll('[data-ymap]').forEach(el=>BT_mapWidget(el));
  BT_phFit();

  /* BreadcrumbList — из хлебных крошек любой страницы */
  const cr=document.querySelector('.crumbs');
  if(cr){
    const items=[...cr.querySelectorAll('a,span')].filter(n=>n.tagName==='A'||!n.querySelector('a'));
    addLd({'@context':'https://schema.org','@type':'BreadcrumbList',itemListElement:items.map((n,i)=>({
      '@type':'ListItem',position:i+1,name:n.textContent.trim(),
      item:n.tagName==='A'?SITE+n.getAttribute('href'):undefined}))});
  }

  /* FAQPage — из любого блока .faq с <details> */
  const faq=document.querySelector('.faq');
  if(faq){
    const qs=[...faq.querySelectorAll('details')].map(d=>({'@type':'Question',
      name:d.querySelector('summary').textContent.trim(),
      acceptedAnswer:{'@type':'Answer',text:[...d.querySelectorAll('summary ~ *')].map(x=>x.textContent.trim()).join(' ')}}));
    if(qs.length) addLd({'@context':'https://schema.org','@type':'FAQPage',mainEntity:qs});
  }

  /* LocalBusiness — на всех страницах */
  addLd({'@context':'https://schema.org','@type':'LocalBusiness','@id':SITE+'#org',
    name:'BEVERTEAM — чай и кофе для дома и бизнеса',legalName:CO.legal,url:SITE,email:CO.mail,
    telephone:[CO.tel1,CO.tel2],priceRange:'650–297000 ₽',
    address:{'@type':'PostalAddress',postalCode:CO.zip,addressLocality:CO.city,streetAddress:CO.street,addressCountry:'RU'},
    openingHoursSpecification:[{'@type':'OpeningHoursSpecification',dayOfWeek:['Monday','Tuesday','Wednesday','Thursday','Friday'],opens:'10:00',closes:'17:00'}],
    taxID:CO.inn,identifier:CO.ogrnip});
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
window.BT_CO_COORDS = [60.685,56.878];   /* ул. Колокольная, 31А — уточнить у клиента */

/* Статичная карта-виджет: работает БЕЗ ключа (конструктор карт Яндекса) */
window.BT_MAP_QUERY = 'Екатеринбург, улица Колокольная, 31А';
window.BT_mapWidget = (el, opts) => {
  if(!el) return;
  /* ищем по адресу: точные координаты не нужны, виджет ставит метку сам */
  const o = Object.assign({text:BT_MAP_QUERY, z:17}, opts||{});
  const src = `https://yandex.ru/map-widget/v1/?text=${encodeURIComponent(o.text)}&z=${o.z}&lang=ru_RU`;
  const f = document.createElement('iframe');
  f.src=src; f.loading='lazy'; f.title='Карта — BEVERTEAM'; f.allowFullscreen=true;
  f.style.cssText='width:100%;height:100%;border:0;display:block';
  /* если виджет не загрузился (нет сети), остаётся подложка-заглушка под ним */
  el.appendChild(f);
  return f;
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
