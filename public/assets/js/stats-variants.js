// Stats Animation JavaScript

/**
 * Animate a counter from start to end value
 * @param {string} elementId - The ID of the element to animate
 * @param {number} start - Starting value
 * @param {number} end - Ending value
 * @param {number} duration - Animation duration in ms
 */
function animateCounter(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const range = end - start;
    const startTime = performance.now();

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.floor(start + (range * easeOutQuart));

        element.textContent = currentValue.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = end.toLocaleString();
            // Add pop animation at the end
            element.classList.add('number-pop');
            setTimeout(() => element.classList.remove('number-pop'), 400);
        }
    }

    requestAnimationFrame(updateCounter);
}

/**
 * Show and animate the stats card on message page after clicking smile
 * @param {object} result - API response with updated stats
 */
function showSmileStats(result) {
    const statsCard = document.getElementById('smile-stats');
    if (!statsCard) return;

    statsCard.classList.remove('hidden');

    // Animate each counter with staggered timing
    setTimeout(() => {
        animateCounter('stats-global-count', result.global_smiles - 1, result.global_smiles, 800);
    }, 100);

    setTimeout(() => {
        animateCounter('stats-company-count', Math.max(0, result.company_smiles - 1), result.company_smiles, 800);
    }, 250);

    setTimeout(() => {
        animateCounter('stats-sender-count', result.sender_smile_count - 1, result.sender_smile_count, 800);
    }, 400);

    // Add celebration animation to the card
    statsCard.classList.add('celebrate-stat');
    setTimeout(() => statsCard.classList.remove('celebrate-stat'), 1000);
}

/**
 * Animate dashboard stats on page load (count up from 0)
 */
function animateDashboardStats() {
    const userCount = document.getElementById('dashboard-user-count');
    const smilesCount = document.getElementById('dashboard-smiles-count');
    const companyCount = document.getElementById('dashboard-company-count');
    const globalCount = document.getElementById('dashboard-global-count');

    if (userCount) {
        const target = parseInt(userCount.dataset.value) || 0;
        animateCounter('dashboard-user-count', 0, target, 1000);
    }

    if (smilesCount) {
        const target = parseInt(smilesCount.dataset.value) || 0;
        setTimeout(() => animateCounter('dashboard-smiles-count', 0, target, 1000), 150);
    }

    if (companyCount) {
        const target = parseInt(companyCount.dataset.value) || 0;
        setTimeout(() => animateCounter('dashboard-company-count', 0, target, 1000), 300);
    }

    if (globalCount) {
        const target = parseInt(globalCount.dataset.value) || 0;
        setTimeout(() => animateCounter('dashboard-global-count', 0, target, 1000), 450);
    }
}

// Run dashboard animation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Only run on dashboard pages
    if (window.location.pathname.includes('/dashboard/')) {
        // Check if we should skip the animation (e.g., after sending a message)
        if (sessionStorage.getItem('skipCounterAnimation')) {
            sessionStorage.removeItem('skipCounterAnimation');
            // Just set the values directly without animation
            const counters = ['dashboard-user-count', 'dashboard-smiles-count', 'dashboard-company-count', 'dashboard-global-count'];
            counters.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = (parseInt(el.dataset.value) || 0).toLocaleString();
                }
            });
        } else {
            animateDashboardStats();
        }
    }
});
