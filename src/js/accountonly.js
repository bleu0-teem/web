// -- LOGIN GUARD --
const storedUser = localStorage.getItem('username');
if (!storedUser) {
  window.location.href = '/login';
  throw new Error('ebat ti lox ebaniy');
}

// -- SHOW USERNAME & LOAD --
document.getElementById('username').textContent = storedUser;