window.toggleLayout = function () {
  const root = document.documentElement;
  const grid = root.classList.toggle('aem-grid');
  localStorage.setItem('aem_layout', grid ? 'grid' : 'list');
};

(function () {
  const pref = localStorage.getItem('aem_layout');
  if (pref === 'grid') document.documentElement.classList.add('aem-grid');
})();


