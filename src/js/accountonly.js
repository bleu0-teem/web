// -- LOGIN GUARD --
const storedUser = localStorage.getItem('username');
if (!storedUser) {
  window.location.href = '/blue16-web/login.html';
  throw new Error('Not logged in; redirecting to login.');
}

// -- SHOW USERNAME & LOAD --
document.getElementById('username').textContent = storedUser;