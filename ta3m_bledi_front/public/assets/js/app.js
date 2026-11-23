(() => {
  const search = document.getElementById('search');
  const category = document.getElementById('category');
  const cards = [...document.querySelectorAll('.workshop-card')];
  const noResults = document.getElementById('noResults');

  function filterCards() {
    if (!search || !category) return;
    const q = search.value.trim().toLocaleLowerCase('fr');
    const cat = category.value;
    let visible = 0;
    cards.forEach(card => {
      const matches = (card.dataset.title.includes(q) || card.dataset.description.includes(q))
        && (!cat || card.dataset.category === cat);
      card.hidden = !matches;
      if (matches) visible++;
    });
    if (noResults) noResults.hidden = visible !== 0;
  }

  search?.addEventListener('input', filterCards);
  category?.addEventListener('change', filterCards);

  const recommendBtn = document.getElementById('recommendBtn');
  recommendBtn?.addEventListener('click', () => {
    const interest = document.getElementById('interest')?.value || '';
    const match = cards.find(card => card.dataset.category === interest);
    const result = document.getElementById('aiResult');
    if (!result) return;
    result.style.display = 'block';
    result.textContent = match
      ? `Suggestion : ${match.querySelector('h3')?.textContent}. ${match.querySelector('.card-body p')?.textContent || ''}`
      : 'Aucun atelier disponible dans cette catégorie pour le moment.';
  });

  document.querySelectorAll('.edit-workshop').forEach(button => {
    button.addEventListener('click', () => {
      const form = document.querySelector('.admin-form');
      if (!form) return;
      const fields = {
        id: button.dataset.id,
        title: button.dataset.title,
        category: button.dataset.category,
        description: button.dataset.description,
        workshop_date: button.dataset.date,
        location: button.dataset.location,
        capacity: button.dataset.capacity,
        icon: button.dataset.icon,
        status: button.dataset.status
      };
      Object.entries(fields).forEach(([name, value]) => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) input.value = value;
      });
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

})();
