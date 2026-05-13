(function () {
  const modal = document.querySelector('[data-qr-modal]');
  const image = document.querySelector('[data-qr-image]');
  const link = document.querySelector('[data-qr-link]');
  const title = document.querySelector('[data-qr-title]');
  const closeButtons = document.querySelectorAll('[data-qr-close]');

  if (!modal || !image || !link) {
    return;
  }

  function openModal(button) {
    const qrUrl = button.getAttribute('data-qr-url') || '';
    const exerciseName = button.getAttribute('data-exercise-name') || 'Exercise';

    if (!qrUrl) {
      return;
    }

    image.src = '../qr_image.php?data=' + encodeURIComponent(qrUrl);
    image.alt = 'QR Code for ' + exerciseName;
    link.href = qrUrl;
    link.textContent = qrUrl;

    if (title) {
      title.textContent = exerciseName + ' QR Code';
    }

    modal.hidden = false;
    modal.classList.add('is-open');
  }

  function closeModal() {
    modal.hidden = true;
    modal.classList.remove('is-open');
    image.removeAttribute('src');
  }

  document.addEventListener('click', function (event) {
    const openButton = event.target.closest('[data-qr-open]');

    if (openButton) {
      openModal(openButton);
      return;
    }

    if (event.target === modal) {
      closeModal();
    }
  });

  closeButtons.forEach(function (button) {
    button.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
