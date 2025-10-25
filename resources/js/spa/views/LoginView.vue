<template>
  <div class="login-page">
    <div class="view-area">
      <section class="card login-card p-4">
        <div class="brand-container mb-4">
          <div class="gs-logo">EGS</div>
          <div class="brand-title">Elevate<span class="smart">GS</span></div>
        </div>

        <h2 class="text-center fw-bold mb-3">Login</h2>
        <p class="text-center text-muted mb-4">Login to ElevateGS</p>

        <div v-if="message" :class="['alert', messageTypeClass]" role="alert">{{ message }}</div>

        <form @submit.prevent="loginSubmit" id="loginForm" novalidate>
          <div class="input-group mb-3">
            <span class="input-group-text text-secondary">
              <i class="bi bi-envelope-at-fill"></i>
            </span>
            <input v-model="login.email" type="text" class="form-control" placeholder="Email" id="email" name="email" required />
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text text-secondary">
              <i class="bi bi-key-fill"></i>
            </span>
            <input v-model="login.password" type="password" class="form-control" placeholder="Password" id="password" name="password" required />
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
        </form>

        <div class="or-separator">OR</div>

        <div id="g_id_onload"
             :data-client_id="googleClientId"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleGoogleCredentialResponse">
        </div>
        <div class="g_id_signin d-flex justify-content-center w-100"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signin_with"
             data-size="large">
        </div>

        <div class="text-center mt-4">
          <small class="text-muted">
            Don't have an account?
            <router-link to="/register" class="text-primary fw-bold">Sign Up</router-link>
          </small>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
import { API_BASE } from '../services/apiBase';
const BACKEND_API_BASE_URL = API_BASE;

// Google Client ID from environment variable
const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID || '651352432780-btgq6d60dl2ga4suk5kn0hce9hhnu9ej.apps.googleusercontent.com';

// Message handling
const message = ref('');
const messageType = ref('danger');
const messageTypeMap = { success: 'alert-success', info: 'alert-info', warning: 'alert-warning', danger: 'alert-danger' };
const messageTypeClass = computed(() => messageTypeMap[messageType.value] || 'alert-danger');

function clearMessage() {
  message.value = '';
}

function showMessage(text, type = 'danger') {
  message.value = text;
  messageType.value = type;
  if (text) {
    setTimeout(() => {
      if (message.value === text) clearMessage();
    }, 6000);
  }
}

// Login state
const login = reactive({ email: '', password: '' });

function roleRedirect(role) {
  if (role === 'admin') return '/admin/dashboard';
  if (role === 'teacher') return '/teacher/dashboard';
  return '/dashboard';
}

function ensureUserIdInLocalStorage(data) {
  // Accept several common fields the backend might return
  const id = data?.userId ?? data?.id ?? data?.user_id ?? data?.uid ?? null;
  if (id) {
    localStorage.setItem('loggedInUserId', id);
    return id;
  }
  // Fallback to a temporary id to satisfy router guards if backend didn't return any id
  const fallback = 'u_' + Date.now();
  localStorage.setItem('loggedInUserId', fallback);
  return fallback;
}

async function handleLoginSuccess(data) {
  try {
    // Store user data
    const userId = ensureUserIdInLocalStorage(data);
    localStorage.setItem('loggedInUserName', data.firstName || data.username || data.name || 'User');
    const role = data.role || 'student';
    localStorage.setItem('loggedInUserRole', role);
    
    // Create auth token for SSE connection
    const authToken = btoa(JSON.stringify({
      userId: userId,
      role: role,
      timestamp: Date.now()
    }));
    localStorage.setItem('sse_auth_token', authToken);  // Use different key to avoid JWT conflict
    console.log('[LoginView] ✅ SSE auth token created for real-time notifications');
    
    // Show success message
    showMessage(data.message || 'Login successful! Redirecting...', 'success');
    
    // Determine destination based on role
    const dest = roleRedirect(role);
    console.log('Login success, redirecting to:', dest);
    
    // Navigate after a short delay to allow message to be seen
    await new Promise(resolve => setTimeout(resolve, 700));
    await router.push(dest);
    
    return true;
  } catch (err) {
    console.error('Login redirect error:', err);
    showMessage('Login successful but redirect failed. Please try again.', 'warning');
    return false;
  }
}

async function loginSubmit() {
  clearMessage();
  
  const email = login.email.trim();
  const password = login.password;

  if (!email || !password) {
    showMessage('Email/username and password are required.');
    return;
  }

  // Email validation
  const lower = email.toLowerCase();
  const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
  if (lower !== 'admin' && !emailPattern.test(email)) {
    showMessage('Please enter a valid email address.');
    return;
  }

  // Admin path: handle special case
  if (email.toLowerCase() === 'admin' && password === 'admin') {
    try {
      const r = await fetch(`${BACKEND_API_BASE_URL}/api/login`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ email: 'admin', password: 'admin' })
      });
      const d = await r.json().catch(()=>({}));
      if(!r.ok){
        showMessage(d.message || 'Login failed. Please check your credentials.');
        return;
      }
      if (d.userId) localStorage.setItem('loggedInUserId', d.userId);
      localStorage.setItem('loggedInUserRole', d.role || 'admin');
      localStorage.setItem('loggedInUserName', d.firstName || 'Administrator');
      
      // Create auth token for SSE connection
      const authToken = btoa(JSON.stringify({
        userId: d.userId,
        role: d.role || 'admin',
        timestamp: Date.now()
      }));
      localStorage.setItem('sse_auth_token', authToken);  // Use different key to avoid JWT conflict
      console.log('[LoginView] ✅ SSE auth token created for admin');
      
      showMessage('Login successful! Redirecting...', 'success');
      try {
        // ensure credentials included and verify server sees admin
        await fetch(`${BACKEND_API_BASE_URL}/api/admin/self?userId=${encodeURIComponent(d.userId)}`, { credentials: 'include', headers: { 'x-user-id': d.userId } })
        const inspect = await fetch(`${BACKEND_API_BASE_URL}/api/debug/auth-inspect`, { credentials: 'include', headers: { 'x-user-id': d.userId } })
        const info = await inspect.json().catch(()=>null)
        if(info && info.exists && info.user && info.user.role === 'admin'){
          // good
        } else {
          showMessage('Server did not recognize admin session after login. Check cookie settings or use Seed Admin (dev).', 'warning')
        }
      } catch(_){ }
      setTimeout(() => router.push('/admin/dashboard'), 600);
      return;
    } catch (e) {
      console.error('Admin bootstrap error', e);
      showMessage('Network error contacting backend.');
      return;
    }
  }

  try {
    const res = await fetch(`${BACKEND_API_BASE_URL}/api/login`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await res.json().catch(() => ({}));

    if (res.ok) {
      showMessage(data.message || 'Login successful! Redirecting...', 'success');
      login.email = '';
      login.password = '';
      if (data.userId) localStorage.setItem('loggedInUserId', data.userId);
      localStorage.setItem('loggedInUserName', data.firstName || data.username || 'User');
      localStorage.setItem('loggedInUserRole', data.role || 'student');
      
      // Create auth token for SSE connection
      const authToken = btoa(JSON.stringify({
        userId: data.userId,
        role: data.role || 'student',
        timestamp: Date.now()
      }));
      localStorage.setItem('sse_auth_token', authToken);  // Use different key to avoid JWT conflict
      console.log('[LoginView] ✅ SSE auth token created for user login');
      
      const dest = roleRedirect(data.role || 'student');
        // Verify server-side session / auth view
        try{
          const userId = data.userId || localStorage.getItem('loggedInUserId')
          const inspect = await fetch(`${BACKEND_API_BASE_URL}/api/debug/auth-inspect`, { credentials: 'include', headers: userId ? { 'x-user-id': userId } : {} })
          const info = await inspect.json().catch(()=>null)
          if(!info || !info.exists || (info.user && String(info.user.id) !== String(userId))){
            showMessage('Logged in locally but server session did not update. Try disabling third-party cookie blockers or click retry after login.', 'warning')
          }
        }catch(e){ console.warn('Post-login auth-inspect failed', e) }
        setTimeout(() => router.push(dest), 700);
    } else {
      showMessage(data.message || 'Login failed. Please check your credentials.');
    }
  } catch (err) {
    console.error('Network error during login:', err);
    showMessage('Could not connect to the server. Please ensure backend is running at http://localhost:3000');
  }
}

// GOOGLE callback (GSI expects a global function)
function showSignInHint() {
  const hint = [
    "Please sign in using the email address you used to register with ElevateGS.",
    "If you're signed into multiple Google accounts, sign out then sign in with the correct account.",
    "Disable ad/privacy extensions or allow third-party cookies if Google sign-in is blocked."
  ].join(" ");
  showMessage(hint, 'warning');
}

async function handleGoogleCredentialResponse(response) {
  clearMessage();
  console.log('GSI credential:', response?.credential ? 'present' : 'missing');

  if (!response?.credential) {
    showSignInHint();
    return;
  }

  // Basic client-side tokeninfo check for immediate feedback
  try {
    const verifyResp = await fetch('https://oauth2.googleapis.com/tokeninfo?id_token=' + encodeURIComponent(response.credential));
    if (!verifyResp.ok) {
      showSignInHint();
      return;
    }
    const payload = await verifyResp.json();
    if (!payload?.email) {
      showMessage('No email returned by Google. Make sure you select an account during sign-in.', 'danger');
      return;
    }
  } catch (err) {
    // continue — token will be validated by backend
    console.warn('Client-side tokeninfo check failed (non-fatal):', err);
  }

  try {
    const res = await fetch(`${BACKEND_API_BASE_URL}/api/google-login`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: response.credential })
    });
    const text = await res.text().catch(() => null);
    
    if (!res.ok) {
      let errorMessage = 'Login failed. Please try again.';
      
      try {
        const errorData = JSON.parse(text);
        if (errorData.message) {
          errorMessage = errorData.message;
          
          // If account not found, add helpful action
          if (res.status === 404) {
            showMessage(errorMessage, 'warning');
            setTimeout(() => {
              if (confirm('Would you like to create a new account with this Google account?')) {
                router.push('/register');
              }
            }, 500);
            return;
          }
        }
      } catch(e) {
        errorMessage = text || `Server error (status ${res.status}).`;
      }
      
      showMessage(errorMessage, 'danger');
      console.error('Google login failed:', res.status, text);
      return;
    }

    const data = JSON.parse(text || '{}');
    await handleLoginSuccess(data);
  } catch (e) {
    console.error('Network/error sending token to backend:', e);
    if (/Failed to fetch|NetworkError/i.test(String(e))) {
      showMessage('Could not reach backend. Ensure backend is running at http://localhost:3000.', 'danger');
    } else {
      showMessage('Failed to process Google sign-in. Disable blockers or try another browser.', 'danger');
    }
  }
}
window.handleGoogleCredentialResponse = handleGoogleCredentialResponse;

onMounted(() => {
  function initGsiOnce() {
    try {
      const onloadEl = document.getElementById('g_id_onload');
      const signinContainer = document.querySelector('.g_id_signin');
      const clientId = onloadEl?.getAttribute('data-client_id') || onloadEl?.getAttribute('data-client-id');
      if (!clientId || !window.google || !window.google.accounts || !window.google.accounts.id) return false;

      // initialize and render the button
      try {
        window.google.accounts.id.initialize({
          client_id: clientId,
          callback: window.handleGoogleCredentialResponse,
          ux_mode: (onloadEl?.getAttribute('data-ux_mode') || 'popup')
        });
        if (signinContainer) {
          window.google.accounts.id.renderButton(signinContainer, {
            theme: signinContainer.getAttribute('data-theme') || 'outline',
            size: signinContainer.getAttribute('data-size') || 'large',
            type: signinContainer.getAttribute('data-type') || 'standard',
            text: signinContainer.getAttribute('data-text') || 'signin_with'
          });
        }
        // optionally show the One Tap prompt (disabled by default to avoid UI surprise)
        // window.google.accounts.id.prompt();
        return true;
      } catch (e) {
        console.warn('GSI init failed', e);
        return false;
      }
    } catch (e) { return false; }
  }

  // Insert the script if not present, and call init when it's ready
  let script = document.querySelector('script[src="https://accounts.google.com/gsi/client"]');
  if (!script) {
    script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.onload = () => {
      // try initializing immediately
      if (!initGsiOnce()) {
        // try a short poll if needed
        const poll = setInterval(() => { if (initGsiOnce()) clearInterval(poll); }, 300);
      }
    };
    document.head.appendChild(script);
  } else {
    // Script exists; try init or poll until available
    if (!initGsiOnce()) {
      const poll = setInterval(() => { if (initGsiOnce()) clearInterval(poll); }, 300);
    }
  }
});
</script>

<style scoped>
.login-page { min-height: 100vh; display:flex; align-items:center; justify-content:center; background:#f8f9fa; }
.view-area { max-width:22rem; padding:0 }
.login-card { width:100%; padding:28px; border-radius:1rem; box-shadow:0 8px 20px rgba(0,0,0,0.08); }
.brand-container { display:flex; align-items:center; gap:.6rem; justify-content:center }
.gs-logo{ background:#ab1818;color:#fff;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:6px;font-weight:700 }
.brand-title{ margin:0;font-size:1rem } .smart{ color:#ab1818 }
.g_id_signin{ width:100% !important; display:flex !important; justify-content:center !important; align-items:center !important }
.or-separator{ display:flex; align-items:center; text-align:center; margin:1rem 0; color:#666 }
.or-separator::before,.or-separator::after{ content:''; flex:1; border-bottom:1px solid #e6e6e6 }
.btn-primary{ background:#ab1818; border:none }
.btn-primary:hover{ background:#8e1515 }
</style>
