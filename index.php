<?php
// index.php - Welcome landing page
session_start();

// Redirect if already logged in
if (!empty($_SESSION['user_id'])){
    $role = $_SESSION['role'] ?? 'patient';
    header("Location: /" . $role . "/dashboard.php");
    exit;
}

// Set page variable to hide awareness banner
$hide_awareness_banner = true;
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Landing page specific styles */
.hero-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem 1rem;
}

.hero-content {
    max-width: 700px;
    animation: fadeInUp 0.8s ease-out;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--bc-primary-dark);
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--bc-gray-600);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.hero-buttons .btn {
    min-width: 150px;
    padding: 0.9rem 2rem;
    font-size: 1.1rem;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 3rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: var(--bc-shadow-md);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--bc-shadow-lg);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--bc-primary-dark);
    margin-bottom: 0.5rem;
}

.feature-title::before {
    content: none !important;
}

.feature-text {
    color: var(--bc-gray-600);
    font-size: 0.95rem;
}

.ribbon-animation {
    display: inline-block;
    font-size: 4rem;
    animation: pulse 2s infinite;
    margin-bottom: 1rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hero-buttons .btn {
        width: 100%;
    }
    
    .ribbon-animation {
        font-size: 3rem;
    }
    
    .features {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575.98px) {
    .hero-section {
        min-height: auto;
        padding: 1.5rem 0.5rem;
    }
    
    .hero-title {
        font-size: 1.75rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
}
</style>

<div class="hero-section">
    <div class="hero-content">
        <div class="ribbon-animation">🎗️</div>
        
        <h1 class="hero-title" style="margin-bottom: 1.5rem;">
            Welcome to OncoSecure
        </h1>
        
        <p class="hero-subtitle">
            Advanced AI-powered breast cancer detection and care platform. 
            Empowering women through early detection and comprehensive support.
        </p>
        
        <div class="hero-buttons">
            <a href="/auth/login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            <a href="/auth/register.php" class="btn btn-secondary">
                <i class="fas fa-user-plus me-2"></i>Register as Patient
            </a>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">AI Detection</h3>
                <p class="feature-text">Advanced artificial intelligence for accurate breast cancer screening</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">👩‍⚕️</div>
                <h3 class="feature-title">Expert Care</h3>
                <p class="feature-text">Connect with experienced oncology specialists</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💕</div>
                <h3 class="feature-title">Support Network</h3>
                <p class="feature-text">Join a community of survivors and fighters</p>
            </div>
        </div>
        
        <div style="margin-top: 3rem; padding: 1.5rem; background: white; border-radius: 15px; box-shadow: var(--bc-shadow-sm);">
            <p style="color: var(--bc-primary); font-weight: 600; margin: 0;">
                💡 Early detection saves lives. Regular screening can detect breast cancer up to 3 years before symptoms appear.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>