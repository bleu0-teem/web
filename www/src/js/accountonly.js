// -- LOGIN GUARD --
const storedUser = localStorage.getItem('username');
if (!storedUser) {
  window.location.href = '/login';
  throw new Error('Error');
}

// -- SHOW USERNAME & LOAD --
document.getElementById('username').textContent = storedUser;