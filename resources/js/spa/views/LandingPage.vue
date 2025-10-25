<template>
  <div class="landing-page">
    <!-- Header -->
    <header class="header">
      <div class="header-container">
        <div class="logo">
          <h2>Elevate<span class="logo-highlight">GS</span></h2>
        </div>
        <nav class="nav">
          <a href="#features" class="nav-link" @click.prevent="scrollToFeatures">Features</a>
          <router-link to="/login" class="btn-nav">
            <span>Login</span>
            <i class="bi bi-box-arrow-in-right"></i>
          </router-link>
        </nav>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-container">
        <div class="hero-content">
          <div class="hero-badge">
            <span class="badge-text">✨ Progressive Web LMS</span>
          </div>
          <h1 class="hero-title">
            Elevate <span class="highlight">GS</span>
          </h1>
          <p class="hero-subtitle">
            Rise Higher. <span class="highlight-text">Work Smart.</span>
          </p>
          <p class="hero-description">
            Transform your graduate management experience with our cutting-edge LMS platform.
            Streamline operations, boost productivity, and create exceptional learning environments.
          </p>
        </div>
        <div class="hero-visual">
          <div class="floating-cards">
            <div class="card card-1">
              <div class="card-icon">
                <i class="bi bi-graph-up"></i>
              </div>
              <h4>Analytics</h4>
              <p>Real-time insights</p>
            </div>
            <div class="card card-2">
              <div class="card-icon">
                <i class="bi bi-people"></i>
              </div>
              <h4>Collaboration</h4>
              <p>Team management</p>
            </div>
            <div class="card card-3">
              <div class="card-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <h4>Security</h4>
              <p>Enterprise-grade</p>
            </div>
          </div>
        </div>
      </div>
      <div class="hero-bg-elements">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
        <div class="bg-shape shape-3"></div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
      <div class="container">
        <div class="section-header">
          <h2>Powerful Features for Modern Education</h2>
          <p>Everything you need to manage, teach, and learn effectively</p>
        </div>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-lightning-charge"></i>
            </div>
            <h3>Intuitive Interface</h3>
            <p>User-friendly design that makes learning management simple and efficient for everyone.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-bar-chart-line"></i>
            </div>
            <h3>Advanced Analytics</h3>
            <p>Comprehensive insights into student performance and course effectiveness.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-device-mobile"></i>
            </div>
            <h3>Mobile-First Design</h3>
            <p>Seamless experience across all devices with PWA capabilities.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-file-earmark-spreadsheet"></i>
            </div>
            <h3>Grade Management</h3>
            <p>Flexible grading systems with automated calculations and reporting.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-calendar-event"></i>
            </div>
            <h3>Smart Scheduling</h3>
            <p>Intelligent calendar management with automated reminders.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="bi bi-shield-lock"></i>
            </div>
            <h3>Enterprise Security</h3>
            <p>Bank-level security with role-based access control.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>Ready to Transform Your Learning Experience?</h2>
          <router-link to="/login" class="btn-primary-large">
            <span>Get Started</span>
            <i class="bi bi-arrow-right"></i>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-copyright">
            <p>&copy; 2025 ElevateGS. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Landing page component with enhanced interactivity
import { onMounted, ref } from 'vue'

const showMobileMenu = ref(false)

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
}

const scrollToFeatures = () => {
  const featuresSection = document.getElementById('features')
  if (featuresSection) {
    const targetPosition = featuresSection.offsetTop - 80 // Account for fixed header
    const startPosition = window.pageYOffset
    const distance = targetPosition - startPosition
    const duration = 1000 // 1 second animation
    let startTime = null

    const animation = (currentTime) => {
      if (startTime === null) startTime = currentTime
      const timeElapsed = currentTime - startTime
      const progress = Math.min(timeElapsed / duration, 1)

      // Easing function for smooth animation
      const easeInOutCubic = progress < 0.5
        ? 4 * progress * progress * progress
        : 1 - Math.pow(-2 * progress + 2, 3) / 2

      window.scrollTo(0, startPosition + distance * easeInOutCubic)

      if (timeElapsed < duration) {
        requestAnimationFrame(animation)
      }
    }

    requestAnimationFrame(animation)
  }
}

onMounted(() => {
  // Add scroll animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-in')
      }
    })
  }, observerOptions)

  // Observe elements for animation
  setTimeout(() => {
    document.querySelectorAll('.feature-card').forEach(el => {
      observer.observe(el)
    })
  }, 100)
})
</script>

<style scoped>
@import "https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap";

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(128, 0, 0, 0.1);
  z-index: 1000;
  transition: all 0.3s ease;
}

.header-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
}

.logo-highlight {
  color: #800000;
}

.nav {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.nav-link {
  color: #666;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.nav-link:hover {
  color: #800000;
}

.btn-nav {
  background: #800000;
  color: white;
  padding: 0.5rem 1.5rem;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-nav:hover {
  background: #600000;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(128, 0, 0, 0.3);
}

/* Global Styles */
.landing-page {
  font-family: 'Inter', sans-serif;
  line-height: 1.6;
  color: #1a1a1a;
  overflow-x: hidden;
}

/* Hero Section */
.hero {
  min-height: 100vh;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 120px 2rem 80px;
}

.hero-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.hero-content {
  color: #1a1a1a;
  animation: slideInLeft 1s ease-out;
}

.hero-badge {
  display: inline-block;
  background: rgba(128, 0, 0, 0.1);
  backdrop-filter: blur(10px);
  padding: 0.5rem 1rem;
  border-radius: 25px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(128, 0, 0, 0.2);
}

.badge-text {
  font-size: 0.9rem;
  font-weight: 600;
  color: #800000;
}

.hero-title {
  font-size: clamp(2.5rem, 5vw, 4rem);
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.1;
}

.hero-title .highlight {
  color: #800000;
}

.hero-subtitle {
  font-size: clamp(1.2rem, 3vw, 1.8rem);
  font-weight: 600;
  margin-bottom: 1.5rem;
  opacity: 0.9;
}

.hero-subtitle .highlight-text {
  color: #800000;
}

.hero-description {
  font-size: 1.1rem;
  line-height: 1.6;
  margin-bottom: 2rem;
  opacity: 0.8;
  max-width: 500px;
}

.btn-primary, .btn-primary-large {
  background: #800000;
  color: white;
  padding: 1rem 2rem;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

.btn-primary:hover, .btn-primary-large:hover {
  background: #600000;
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(128, 0, 0, 0.3);
}

.btn-primary-large {
  padding: 1.2rem 2.5rem;
  font-size: 1.1rem;
}

/* Hero Visual */
.hero-visual {
  position: relative;
  animation: slideInRight 1s ease-out;
}

.floating-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  max-width: 400px;
}

.card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 1.5rem;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  animation: float 6s ease-in-out infinite;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.card-1 { animation-delay: 0s; }
.card-2 { animation-delay: 2s; }
.card-3 { animation-delay: 4s; }

.card-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #800000, #a00000);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  font-size: 1.5rem;
}

.card h4 {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.card p {
  color: #666;
  font-size: 0.9rem;
  margin: 0;
}

/* Background Elements */
.hero-bg-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  z-index: 0;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 8s ease-in-out infinite;
}

.shape-1 {
  width: 300px;
  height: 300px;
  top: 10%;
  right: 10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 200px;
  height: 200px;
  top: 60%;
  left: 10%;
  animation-delay: 3s;
}

.shape-3 {
  width: 150px;
  height: 150px;
  bottom: 20%;
  right: 20%;
  animation-delay: 6s;
}

/* Features Section */
.features {
  padding: 100px 2rem;
  background: #f8f9fa;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-header h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.section-header p {
  font-size: 1.2rem;
  color: #666;
  max-width: 600px;
  margin: 0 auto;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.feature-card {
  background: white;
  padding: 2.5rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
  transition: all 0.3s ease;
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.6s ease;
}

.feature-card.animate-in {
  opacity: 1;
  transform: translateY(0);
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.feature-icon {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, #800000, #a00000);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: white;
  font-size: 2rem;
}

.feature-card h3 {
  font-size: 1.4rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 1rem;
}

.feature-card p {
  color: #666;
  line-height: 1.6;
}

/* CTA Section */
.cta {
  padding: 100px 2rem;
  background: #1a1a1a;
  color: white;
}

.cta-content {
  text-align: center;
  max-width: 600px;
  margin: 0 auto;
}

.cta-content h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  margin-bottom: 1rem;
}

.cta-content p {
  font-size: 1.2rem;
  opacity: 0.8;
  margin-bottom: 2rem;
}

/* Footer */
.footer {
  padding: 60px 2rem 30px;
  background: #111;
  color: #999;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  align-items: start;
}

.footer-copyright {
  text-align: center;
  grid-column: 1 / -1;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #333;
}

/* Animations */
@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-container {
    padding: 1rem;
  }

  .nav {
    gap: 1rem;
  }

  .nav-link {
    display: none;
  }

  .logo {
    font-size: 1.3rem;
  }

  .nav-container {
    padding: 0 1rem;
  }

  .nav-links {
    gap: 1rem;
  }

  .hero-container {
    grid-template-columns: 1fr;
    gap: 2rem;
    text-align: center;
  }

  .hero-actions {
    justify-content: center;
  }

  .floating-cards {
    grid-template-columns: 1fr;
    max-width: 300px;
    margin: 0 auto;
  }

  .features {
    padding: 60px 1rem;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .cta {
    padding: 60px 1rem;
  }

  .footer {
    padding: 40px 1rem 20px;
  }
}

@media (max-width: 480px) {
  .hero {
    padding: 120px 1rem 60px;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-actions {
    flex-direction: column;
    align-items: center;
  }

  .btn-primary, .btn-secondary {
    width: 100%;
    justify-content: center;
  }

  .footer-content {
    text-align: center;
  }
}
</style>