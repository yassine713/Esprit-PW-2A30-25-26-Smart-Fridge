<?php
require_once __DIR__ . '/../controller/LoginController.php';

$loginController = new LoginController();
[
  'error' => $error,
  'captchaToken' => $captchaToken,
  'showForgotPassword' => $showForgotPassword,
  'resetModalOpen' => $resetModalOpen,
  'resetStep' => $resetStep,
  'resetError' => $resetError,
  'resetMessage' => $resetMessage,
  'submittedEmail' => $submittedEmail
] = $loginController->handle();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Sign in</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .robot-check {
      display: grid;
      gap: 6px;
    }

    .robot-box {
      align-items: center;
      border: 1px solid var(--user-line);
      border-radius: var(--user-radius);
      cursor: pointer;
      display: flex;
      gap: 10px;
      padding: 12px;
    }

    .robot-box input {
      display: none;
    }

    .robot-square {
      align-items: center;
      border: 2px solid var(--user-line);
      border-radius: 4px;
      display: inline-flex;
      height: 22px;
      justify-content: center;
      width: 22px;
    }

    .robot-box input:checked + .robot-square {
      background: var(--user-primary);
      border-color: var(--user-primary);
    }

    .robot-box input:checked + .robot-square::after {
      color: #fff;
      content: "\2713";
      font-weight: 800;
    }

    .robot-status {
      margin: 0;
    }

    .robot-modal {
      align-items: center;
      background: rgba(17, 24, 19, 0.45);
      display: none;
      inset: 0;
      justify-content: center;
      padding: 20px;
      position: fixed;
      z-index: 50;
    }

    .robot-modal.is-open {
      display: flex;
    }

    .robot-card {
      max-width: 440px;
      position: relative;
      width: min(100%, 440px);
    }

    .robot-close {
      background: transparent;
      border: 0;
      color: var(--user-muted);
      cursor: pointer;
      font-size: 28px;
      line-height: 1;
      position: absolute;
      right: 18px;
      top: 14px;
    }

    .robot-methods,
    .robot-images {
      display: grid;
      gap: 12px;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      margin-top: 14px;
    }

    .robot-challenge {
      margin-top: 18px;
    }

    .robot-question {
      color: var(--user-ink);
      font-weight: 800;
      margin: 0 0 12px;
    }

    .robot-image-choice {
      background: var(--user-panel);
      border: 1px solid var(--user-line);
      border-radius: var(--user-radius);
      color: var(--user-ink);
      cursor: pointer;
      display: grid;
      gap: 8px;
      min-height: 128px;
      padding: 16px;
      place-items: center;
    }

    .robot-image-choice img {
      display: block;
      height: 68px;
      object-fit: contain;
      width: 92px;
    }

    .robot-word {
      background: var(--user-panel);
      border: 1px dashed var(--user-primary);
      border-radius: var(--user-radius);
      color: var(--user-primary-deep);
      font-size: 28px;
      font-style: italic;
      font-weight: 900;
      letter-spacing: 0;
      margin-bottom: 12px;
      padding: 12px;
      text-align: center;
      transform: skew(-8deg);
    }

    #text-answer {
      margin-bottom: 8px;
      width: 100%;
    }

    .forgot-action {
      background: transparent;
      border: 0;
      color: var(--user-primary);
      cursor: pointer;
      font: inherit;
      font-weight: 800;
      padding: 0;
      text-align: left;
    }

    .reset-form {
      display: grid;
      gap: 12px;
      margin-top: 16px;
    }

    .reset-form input {
      width: 100%;
    }

    @media (max-width: 520px) {
      .robot-methods,
      .robot-images {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="app" data-view="login">
    <aside class="auth-hero">
      <div class="brand">
        <div class="brand-mark"></div>
        <div>
          <h1>NutriBudget</h1>
          <p>Smart nutrition on a budget</p>
        </div>
      </div>

      <div class="card auth-card" data-screen="login">
        <h2>Welcome back</h2>
        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" id="login-form" novalidate>
          <label>
            <span>Email address</span>
            <input type="text" name="email" id="login-email" placeholder="you@example.com" value="<?= htmlspecialchars($submittedEmail) ?>" />
            <small class="error" data-error-for="login-email"></small>
          </label>
          <label>
            <span>Password</span>
            <input type="password" name="password" id="login-password" placeholder="Enter your password" />
            <small class="error" data-error-for="login-password"></small>
          </label>
          <div class="robot-check">
            <label class="robot-box">
              <input type="checkbox" id="robot-trigger" />
              <span class="robot-square"></span>
              <span>I am not a robot</span>
            </label>
            <input type="hidden" name="captcha_token" id="captcha-token" value="" />
            <small class="error" data-error-for="robot-check"></small>
            <p class="muted robot-status" id="robot-status">Verification required before sign in.</p>
          </div>
          <button class="btn primary" type="submit">Sign in</button>
          <?php if ($showForgotPassword): ?>
            <button class="forgot-action" type="button" id="forgot-password-link">Forgot password?</button>
          <?php endif; ?>
        </form>
        <?php if ($resetMessage && !$resetModalOpen): ?>
          <p class="muted"><?= htmlspecialchars($resetMessage) ?></p>
        <?php endif; ?>
        <p class="muted">Don't have an account? <a class="link" href="signup.php">Sign up</a></p>
      </div>
    </aside>

    <section class="auth-panel">
      <div class="panel-content" data-screen="login">
        <div class="icon-badge">NB</div>
        <h2>Healthy eating made simple</h2>
        <p>Track your nutrition, manage your budget, and achieve your health goals with AI-powered insights.</p>
      </div>
    </section>
  </div>

  <div class="robot-modal" id="robot-modal" aria-hidden="true">
    <div class="card robot-card" role="dialog" aria-modal="true" aria-labelledby="robot-title">
      <button class="robot-close" type="button" id="robot-close" aria-label="Close verification">x</button>
      <h2 id="robot-title">Robot verification</h2>
      <p class="muted" id="robot-help">Choose a verification method.</p>

      <div class="robot-methods" id="robot-methods">
        <button class="btn" type="button" id="image-challenge-btn">Image</button>
        <button class="btn" type="button" id="text-challenge-btn">Text</button>
      </div>

      <div class="robot-challenge" id="image-challenge" hidden>
        <p class="robot-question" id="image-question"></p>
        <div class="robot-images" id="image-options"></div>
        <small class="error" id="image-error"></small>
      </div>

      <div class="robot-challenge" id="text-challenge" hidden>
        <p class="robot-question">Rewrite this word exactly:</p>
        <div class="robot-word" id="text-word"></div>
        <input type="text" id="text-answer" placeholder="Type the word here" autocomplete="off" />
        <small class="error" id="text-error"></small>
        <button class="btn primary" type="button" id="text-verify">Verify</button>
      </div>
    </div>
  </div>

  <div class="robot-modal <?= $resetModalOpen ? 'is-open' : '' ?>" id="reset-modal" aria-hidden="<?= $resetModalOpen ? 'false' : 'true' ?>">
    <div class="card robot-card" role="dialog" aria-modal="true" aria-labelledby="reset-title">
      <button class="robot-close" type="button" id="reset-close" aria-label="Close password recovery">x</button>
      <h2 id="reset-title">Forgot password?</h2>
      <p class="muted">Use the 4-number recovery code you created during sign up.</p>

      <?php if ($resetError): ?>
        <p class="error"><?= htmlspecialchars($resetError) ?></p>
      <?php endif; ?>
      <?php if ($resetMessage && $resetModalOpen): ?>
        <p class="muted"><?= htmlspecialchars($resetMessage) ?></p>
      <?php endif; ?>

      <?php if ($resetStep === 'password'): ?>
        <form method="post" class="reset-form" id="reset-password-form" novalidate>
          <input type="hidden" name="reset_action" value="update_password" />
          <label>
            <span>New password</span>
            <input type="password" name="new_password" id="new-password" placeholder="Create a new password" />
            <small class="error" data-error-for="new-password"></small>
          </label>
          <button class="btn primary" type="submit">Update password</button>
        </form>
      <?php else: ?>
        <form method="post" class="reset-form" id="recovery-code-form" novalidate>
          <input type="hidden" name="reset_action" value="verify_recovery" />
          <input type="hidden" name="reset_email" id="reset-email" value="<?= htmlspecialchars($submittedEmail) ?>" />
          <label>
            <span>Enter your recovery code</span>
            <input type="text" name="recovery_code" id="recovery-code" placeholder="4 numbers" maxlength="4" inputmode="numeric" />
            <small class="error" data-error-for="recovery-code"></small>
          </label>
          <button class="btn primary" type="submit">Continue</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const loginForm = document.getElementById('login-form');
    const loginEmail = document.getElementById('login-email');
    const loginPassword = document.getElementById('login-password');
    const robotTrigger = document.getElementById('robot-trigger');
    const robotModal = document.getElementById('robot-modal');
    const robotClose = document.getElementById('robot-close');
    const robotStatus = document.getElementById('robot-status');
    const captchaToken = document.getElementById('captcha-token');
    const imageChallengeBtn = document.getElementById('image-challenge-btn');
    const textChallengeBtn = document.getElementById('text-challenge-btn');
    const imageChallenge = document.getElementById('image-challenge');
    const textChallenge = document.getElementById('text-challenge');
    const imageQuestion = document.getElementById('image-question');
    const imageOptions = document.getElementById('image-options');
    const imageError = document.getElementById('image-error');
    const textWord = document.getElementById('text-word');
    const textAnswer = document.getElementById('text-answer');
    const textError = document.getElementById('text-error');
    const textVerify = document.getElementById('text-verify');
    const forgotPasswordLink = document.getElementById('forgot-password-link');
    const resetModal = document.getElementById('reset-modal');
    const resetClose = document.getElementById('reset-close');
    const resetEmail = document.getElementById('reset-email');
    const recoveryCodeForm = document.getElementById('recovery-code-form');
    const recoveryCode = document.getElementById('recovery-code');
    const resetPasswordForm = document.getElementById('reset-password-form');
    const newPassword = document.getElementById('new-password');
    const validCaptchaToken = '<?= htmlspecialchars($captchaToken, ENT_QUOTES) ?>';
    let robotVerified = false;
    let currentTextWord = '';

    const imageChallenges = [
      {
        question: 'Choose the dog.',
        answer: 'dog',
        options: [
          { key: 'dog', label: 'Dog', picture: robotImage('dog') },
          { key: 'cat', label: 'Cat', picture: robotImage('cat') }
        ]
      },
      {
        question: 'Choose the laptop.',
        answer: 'laptop',
        options: [
          { key: 'laptop', label: 'Laptop', picture: robotImage('laptop') },
          { key: 'phone', label: 'Phone', picture: robotImage('phone') }
        ]
      },
      {
        question: 'Choose the car.',
        answer: 'car',
        options: [
          { key: 'car', label: 'Car', picture: robotImage('car') },
          { key: 'bike', label: 'Bike', picture: robotImage('bike') }
        ]
      }
    ];

    const textChallenges = ['NuTri42', 'FitMeal7', 'BuDget9'];

    function robotImage(type) {
      const images = {
        dog: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#eef7ef"/><circle cx="60" cy="44" r="25" fill="#b8783f"/><circle cx="38" cy="35" r="13" fill="#8b552d"/><circle cx="82" cy="35" r="13" fill="#8b552d"/><circle cx="51" cy="40" r="4" fill="#111813"/><circle cx="69" cy="40" r="4" fill="#111813"/><ellipse cx="60" cy="52" rx="6" ry="4" fill="#111813"/><path d="M51 61 Q60 69 69 61" fill="none" stroke="#111813" stroke-width="4" stroke-linecap="round"/></svg>',
        cat: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#fff4ea"/><path d="M38 30 L48 12 L58 31 Z" fill="#d99153"/><path d="M62 31 L72 12 L82 30 Z" fill="#d99153"/><circle cx="60" cy="46" r="25" fill="#e7a765"/><circle cx="51" cy="43" r="4" fill="#111813"/><circle cx="69" cy="43" r="4" fill="#111813"/><path d="M60 50 L55 57 H65 Z" fill="#111813"/><path d="M34 52 H50 M70 52 H86 M35 61 H51 M69 61 H85" stroke="#111813" stroke-width="3" stroke-linecap="round"/></svg>',
        laptop: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#eef5ff"/><rect x="28" y="20" width="64" height="44" rx="5" fill="#233449"/><rect x="34" y="26" width="52" height="32" rx="3" fill="#2d8cff"/><path d="M20 68 H100 L91 78 H29 Z" fill="#111813"/><rect x="52" y="70" width="16" height="3" rx="1.5" fill="#ffffff"/></svg>',
        phone: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#eef7ef"/><rect x="43" y="12" width="34" height="66" rx="8" fill="#111813"/><rect x="47" y="20" width="26" height="48" rx="3" fill="#16a35b"/><circle cx="60" cy="73" r="2.5" fill="#ffffff"/></svg>',
        car: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#fff2ee"/><path d="M28 52 L39 34 H76 L91 52 Z" fill="#ff6b4a"/><rect x="22" y="50" width="76" height="20" rx="8" fill="#e84d2d"/><path d="M44 38 H58 V50 H36 Z M62 38 H75 L84 50 H62 Z" fill="#ffffff"/><circle cx="39" cy="70" r="8" fill="#111813"/><circle cx="81" cy="70" r="8" fill="#111813"/></svg>',
        bike: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 90"><rect width="120" height="90" rx="12" fill="#eefaf9"/><circle cx="35" cy="62" r="16" fill="none" stroke="#111813" stroke-width="5"/><circle cx="85" cy="62" r="16" fill="none" stroke="#111813" stroke-width="5"/><path d="M35 62 L52 38 L67 62 H35 L58 62 L85 62 L70 38 H52" fill="none" stroke="#0ea5a4" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/><path d="M67 38 H79 M52 38 L48 28 M44 28 H54" stroke="#111813" stroke-width="5" stroke-linecap="round"/></svg>'
      };

      return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(images[type]);
    }

    function setError(id, message) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = message;
    }

    function clearError(id) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = '';
    }

    function randomItem(items) {
      return items[Math.floor(Math.random() * items.length)];
    }

    function shuffle(items) {
      return [...items].sort(() => Math.random() - 0.5);
    }

    function openRobotModal() {
      robotModal.classList.add('is-open');
      robotModal.setAttribute('aria-hidden', 'false');
      imageChallenge.hidden = true;
      textChallenge.hidden = true;
      imageError.textContent = '';
      textError.textContent = '';
      textAnswer.value = '';
    }

    function closeRobotModal() {
      robotModal.classList.remove('is-open');
      robotModal.setAttribute('aria-hidden', 'true');
      if (!robotVerified) robotTrigger.checked = false;
    }

    function completeRobotCheck() {
      robotVerified = true;
      robotTrigger.checked = true;
      robotTrigger.disabled = true;
      captchaToken.value = validCaptchaToken;
      robotStatus.textContent = 'Verification completed.';
      clearError('robot-check');
      closeRobotModal();
    }

    function showImageChallenge() {
      const challenge = randomItem(imageChallenges);
      imageQuestion.textContent = challenge.question;
      imageOptions.innerHTML = '';
      imageError.textContent = '';
      shuffle(challenge.options).forEach((option) => {
        const button = document.createElement('button');
        button.className = 'robot-image-choice';
        button.type = 'button';
        button.innerHTML = `<img src="${option.picture}" alt="${option.label}"><strong>${option.label}</strong>`;
        button.addEventListener('click', () => {
          if (option.key === challenge.answer) completeRobotCheck();
          else imageError.textContent = 'Wrong image. Try again.';
        });
        imageOptions.appendChild(button);
      });
      textChallenge.hidden = true;
      imageChallenge.hidden = false;
    }

    function showTextChallenge() {
      currentTextWord = randomItem(textChallenges);
      textWord.textContent = currentTextWord;
      textError.textContent = '';
      textAnswer.value = '';
      imageChallenge.hidden = true;
      textChallenge.hidden = false;
      textAnswer.focus();
    }

    robotTrigger.addEventListener('change', () => {
      if (robotTrigger.checked && !robotVerified) openRobotModal();
    });

    robotClose.addEventListener('click', closeRobotModal);
    imageChallengeBtn.addEventListener('click', showImageChallenge);
    textChallengeBtn.addEventListener('click', showTextChallenge);
    textVerify.addEventListener('click', () => {
      if (textAnswer.value === currentTextWord) {
        completeRobotCheck();
      } else {
        textError.textContent = 'The word must match exactly.';
      }
    });

    if (forgotPasswordLink) {
      forgotPasswordLink.addEventListener('click', () => {
        if (resetEmail) resetEmail.value = loginEmail.value.trim();
        resetModal.classList.add('is-open');
        resetModal.setAttribute('aria-hidden', 'false');
        if (recoveryCode) recoveryCode.focus();
      });
    }

    if (resetClose) {
      resetClose.addEventListener('click', () => {
        resetModal.classList.remove('is-open');
        resetModal.setAttribute('aria-hidden', 'true');
      });
    }

    if (recoveryCodeForm) {
      recoveryCodeForm.addEventListener('submit', (e) => {
        const codeOk = /^\d{4}$/.test(recoveryCode.value.trim());
        if (resetEmail) resetEmail.value = loginEmail.value.trim();
        if (!codeOk) {
          setError('recovery-code', 'Recovery code must be exactly 4 numbers.');
          e.preventDefault();
        } else {
          clearError('recovery-code');
        }
      });
    }

    if (resetPasswordForm) {
      resetPasswordForm.addEventListener('submit', (e) => {
        if (newPassword.value.trim().length < 4) {
          setError('new-password', 'Password must be at least 4 characters.');
          e.preventDefault();
        } else {
          clearError('new-password');
        }
      });
    }

    loginForm.addEventListener('submit', (e) => {
      let ok = true;
      const email = loginEmail.value.trim();
      const pass = loginPassword.value.trim();
      const emailOk = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(email);

      if (!emailOk) { setError('login-email', 'Enter a valid email.'); ok = false; }
      else clearError('login-email');

      if (pass.length < 4) { setError('login-password', 'Password must be at least 4 characters.'); ok = false; }
      else clearError('login-password');

      if (!robotVerified) { setError('robot-check', 'Complete the robot verification.'); ok = false; }
      else clearError('robot-check');

      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
