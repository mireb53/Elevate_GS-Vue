&lt;template>
  &lt;div class="register-page">
    &lt;section class="card register-card mx-auto">
      &lt;div class="brand-container mb-3">
        &lt;div class="gs-logo">GS&lt;/div>
        &lt;h5 class="register-title">Grad&lt;span class="smart">Smart&lt;/span>&lt;/h5>
      &lt;/div>

      &lt;h2 class="text-center fw-bold mb-2">Register&lt;/h2>
      &lt;p class="text-center text-muted mb-4">Create your ElevateGS account&lt;/p>

      &lt;div :class="['alert', messageTypeClass, message ? '' : 'd-none']" role="alert">{{ message }}&lt;/div>

      &lt;form id="registerForm" @submit.prevent="registerSubmit">
        &lt;div class="input-group mb-3">
          &lt;span class="input-group-text">&lt;i class="bi bi-person-circle">&lt;/i>&lt;/span>
          &lt;input v-model="register.firstName" type="text" autocomplete="given-name" class="form-control" placeholder="First Name" id="firstName" name="firstName" required>
        &lt;/div>

        &lt;div class="input-group mb-3">
          &lt;span class="input-group-text">&lt;i class="bi bi-person-badge-fill">&lt;/i>&lt;/span>
          &lt;input v-model="register.lastName" type="text" autocomplete="family-name" class="form-control" placeholder="Last Name" id="lastName" name="lastName" required>
        &lt;/div>

        &lt;div class="input-group mb-3">
          &lt;span class="input-group-text">&lt;i class="bi bi-envelope-fill">&lt;/i>&lt;/span>
          &lt;input v-model="register.email" type="email" autocomplete="email" class="form-control" placeholder="Email" id="emailRegister" name="email" required>
          &lt;button id="sendCodeBtn" type="button" class="btn btn-primary" :disabled="sendCodeDisabled" @click="sendVerificationCode">{{ sendCodeText }}&lt;/button>
        &lt;/div>

        &lt;div class="input-group mb-3" v-if="verifyRowVisible" id="verifyRow">
          &lt;span class="input-group-text">&lt;i class="bi bi-shield-check">&lt;/i>&lt;/span>
          &lt;input v-model="register.code" type="text" autocomplete="one-time-code" class="form-control" placeholder="Enter verification code" id="emailCode" maxlength="8">
          &lt;button id="verifyCodeBtn" type="button" class="btn btn-primary" @click="verifyCode" :disabled="verifyCodeDisabled">Verify&lt;/button>
        &lt;/div>

        &lt;div class="input-group mb-3">
          &lt;span class="input-group-text">&lt;i class="bi bi-key-fill">&lt;/i>&lt;/span>
          &lt;input v-model="register.password" type="password" autocomplete="new-password" class="form-control" placeholder="Password" id="password" name="password" required>
        &lt;/div>

        <template>
          <div class="register-page">
            <section class="card register-card mx-auto">
      <div class="brand-container mb-3">
        <div class="gs-logo">EGS</div>
        <h5 class="register-title">Elevate<span class="smart">GS</span></h5>
      </div>              <h2 class="text-center fw-bold mb-2">Register</h2>
              <p class="text-center text-muted mb-4">Create your ElevateGS account</p>

              <div :class="['alert', messageTypeClass, message ? '' : 'd-none']" role="alert">{{ message }}</div>

              <form id="registerForm" @submit.prevent="registerSubmit">
                <div class="input-group mb-3">
                  <span class="input-group-text"><i class="bi bi-person-circle"></i></span>
                  <input v-model="register.firstName" type="text" autocomplete="given-name" class="form-control" placeholder="First Name" id="firstName" name="firstName" required>
                </div>

                <div class="input-group mb-3">
                  <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                  <input v-model="register.lastName" type="text" autocomplete="family-name" class="form-control" placeholder="Last Name" id="lastName" name="lastName" required>
                </div>

                <div class="input-group mb-3">
                  <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                  <input v-model="register.email" type="email" autocomplete="email" class="form-control" placeholder="Email" id="emailRegister" name="email" required>
                  <button id="sendCodeBtn" type="button" class="btn btn-primary" :disabled="sendCodeDisabled" @click="sendVerificationCode">{{ sendCodeText }}</button>
                </div>

                <div class="input-group mb-3" v-if="verifyRowVisible" id="verifyRow">
                  <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                  <input v-model="register.code" type="text" autocomplete="one-time-code" class="form-control" placeholder="Enter verification code" id="emailCode" maxlength="8">
                  <button id="verifyCodeBtn" type="button" class="btn btn-primary" @click="verifyCode" :disabled="verifyCodeDisabled">Verify</button>
                </div>

                <div class="input-group mb-3">
                  <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                  <input v-model="register.password" type="password" autocomplete="new-password" class="form-control" placeholder="Password" id="password" name="password" required>
                </div>

                <div class="input-group mb-3">
                  <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                  <input v-model="register.confirmPassword" type="password" autocomplete="new-password" class="form-control" placeholder="Confirm Password" id="confirmPassword" name="confirmPassword" required>
                </div>

                <div class="mb-3">
                  <label class="form-label d-block">Account role</label>
                  <div class="btn-group" role="group" aria-label="Role selection">
                    <input class="btn-check" type="radio" name="role" value="student" id="roleStudent" autocomplete="off" v-model="register.role" checked>
                    <label class="btn btn-outline-primary" for="roleStudent"><i class="bi bi-mortarboard-fill me-1"></i> Student</label>

                    <input class="btn-check" type="radio" name="role" value="teacher" id="roleTeacher" autocomplete="off" v-model="register.role">
                    <label class="btn btn-outline-primary" for="roleTeacher"><i class="bi bi-person-workspace me-1"></i> Teacher</label>
                  </div>
                </div>

                <button class="btn btn-register w-100" type="submit" :disabled="!emailVerified">Register</button>
              </form>

              <div class="text-center mt-4">
                <small class="text-muted">
                  Already have an account?
                  <router-link to="/login" class="text-primary fw-semibold">Login</router-link>
                </small>
              </div>
            </section>
          </div>
        </template>

        <script setup>
        import { ref, reactive, computed } from 'vue';
        import { useRouter } from 'vue-router';

  const router = useRouter();
  import { API_BASE } from '../services/apiBase';
  const BACKEND_API_BASE_URL = API_BASE;

        // Message handling
        const message = ref('');
        const messageType = ref('danger');
        const messageTypeMap = { success: 'alert-success', info: 'alert-info', warning: 'alert-warning', danger: 'alert-danger' };
        const messageTypeClass = computed(() => messageTypeMap[messageType.value] || 'alert-danger');

        function showMessage(text, type = 'danger') {
          message.value = text;
          messageType.value = type;
          if (text) {
            setTimeout(() => {
              if (message.value === text) message.value = '';
            }, 6000);
          }
        }

        // Register state
        const register = reactive({
          firstName: '',
          lastName: '',
          email: '',
          password: '',
          confirmPassword: '',
          role: 'student',
          code: ''
        });

        const emailVerified = ref(false);
        const verifyRowVisible = ref(false);
        const sendCodeDisabled = ref(false);
        const verifyCodeDisabled = ref(false);
        const sendCodeText = ref('Send code');
        let resendTimer = null;

        function startResendCooldown() {
          let remaining = 60;
          sendCodeDisabled.value = true;
          sendCodeText.value = `Resend (${remaining}s)`;
          resendTimer = setInterval(() => {
            remaining--;
            sendCodeText.value = remaining > 0 ? `Resend (${remaining}s)` : 'Send code';
            if (remaining <= 0) {
              clearInterval(resendTimer);
              resendTimer = null;
              sendCodeDisabled.value = false;
            }
          }, 1000);
        }

        async function sendVerificationCode() {
          const email = register.email.trim();
          if (!email) {
            showMessage('Enter an email address first.', 'warning');
            return;
          }
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailPattern.test(email)) {
            showMessage('Please enter a valid email address.', 'warning');
            return;
          }
          sendCodeDisabled.value = true;
          try {
            const res = await fetch(`${BACKEND_API_BASE_URL}/api/send-verification-code`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
              verifyRowVisible.value = true;
              startResendCooldown();
              showMessage(data.message || 'Verification code sent.', 'success');
            } else if (res.status === 409) {
              showMessage(data.message || 'Email already registered', 'warning');
            } else {
              showMessage(data.message || `Failed to send verification code (status ${res.status}).`, 'danger');
            }
          } catch (err) {
            console.error(err);
            showMessage('Could not reach backend.', 'danger');
          }
          sendCodeDisabled.value = false;
        }

        async function verifyCode() {
          const email = register.email.trim();
          const code = register.code.trim();
          if (!email || !code) {
            showMessage('Enter both email and verification code.', 'warning');
            return;
          }
          verifyCodeDisabled.value = true;
          try {
            const res = await fetch(`${BACKEND_API_BASE_URL}/api/verify-email-code`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email, code })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
              emailVerified.value = true;
              showMessage(data.message || 'Email verified.', 'success');
              verifyRowVisible.value = false;
            } else {
              showMessage(data.message || 'Verification failed.', 'danger');
            }
          } catch (err) {
            console.error(err);
            showMessage('Could not reach backend.', 'danger');
          }
          verifyCodeDisabled.value = false;
        }

        async function registerSubmit() {
          if (!emailVerified.value) {
            showMessage('Please verify your email before registering.', 'warning');
            return;
          }
          if (register.password.length < 6) {
            showMessage('Password must be at least 6 characters long.', 'warning');
            return;
          }
          if (register.password !== register.confirmPassword) {
            showMessage('Passwords do not match.', 'warning');
            return;
          }
          try {
            const res = await fetch(`${BACKEND_API_BASE_URL}/api/register`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                firstName: register.firstName,
                lastName: register.lastName,
                email: register.email,
                password: register.password,
                role: register.role
              })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
              showMessage(data.message || 'Registration successful! Redirecting...', 'success');
              setTimeout(() => {
                router.push('/login');
              }, 1200);
            } else if (res.status === 409) {
              showMessage(data.message || 'This email is already registered.', 'warning');
            } else {
              showMessage(data.message || 'Registration failed.', 'danger');
            }
          } catch (err) {
            console.error(err);
            showMessage('Network error.', 'danger');
          }
        }
        </script>

        <style scoped>
        .register-page {
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: #f8f9fa;
          padding: 2rem;
        }

        .register-card {
          border: none;
          border-radius: 1rem;
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
          padding: 2rem;
          width: 100%;
          max-width: 420px;
        }

        .brand-container {
          display: flex;
          align-items: center;
          gap: 0.6rem;
          justify-content: center;
          margin-bottom: 1.5rem;
        }

        .gs-logo {
          background: #ab1818;
          color: white;
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 6px;
          font-weight: 700;
        }

        .register-title {
          font-size: 1.1rem;
          font-weight: 700;
          margin: 0;
        }

        .smart {
          color: #ab1818;
        }

        .btn-register {
          background-color: #ab1818 !important;
          border: none !important;
          border-radius: 0.5rem;
          color: #fff !important;
          font-weight: 500;
          padding: 0.6rem 1rem;
          transition: all 0.3s ease;
        }

        .btn-register:hover {
          background-color: #8e1515 !important;
          transform: translateY(-1px);
          box-shadow: 0 4px 8px rgba(171, 24, 24, 0.3);
        }

        .input-group .btn-primary {
          background-color: #ab1818;
          border: none;
        }

        .btn-outline-primary {
          color: #ab1818;
          border-color: #ab1818;
        }

        .btn-outline-primary.active,
        .btn-outline-primary:focus,
        .btn-check:checked + .btn-outline-primary {
          background-color: #ab1818;
          color: #fff;
          border-color: #ab1818;
        }

        .alert.d-none {
          display: none;
        }
        </style>
