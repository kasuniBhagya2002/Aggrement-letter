        </div>
    </div>

    <script>
    // Navigation active state management
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle (for future enhancement)
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Auto-update copyright year
        const yearSpan = document.getElementById('current-year');
        if (yearSpan) {
            yearSpan.textContent = new Date().getFullYear();
        }
    });
    </script>
</body>
</html>