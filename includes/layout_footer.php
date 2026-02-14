    </div> <!-- Penutup Main Content -->
</div> <!-- Penutup Flex Container -->

<script>
// Sidebar Mobile Control - PASTI JALAN!
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuBtn = document.getElementById('menuBtn');
    const closeBtn = document.getElementById('closeBtn');
    
    // Fungsi buka sidebar
    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Cegah scroll
    }
    
    // Fungsi tutup sidebar
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        overlay.classList.add('hidden');
        document.body.style.overflow = ''; // Kembalikan scroll
    }
    
    // Event listener untuk tombol menu
    if (menuBtn) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            openSidebar();
        });
    }
    
    // Event listener untuk tombol close
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    // Event listener untuk overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Handle resize window
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            // Desktop: sidebar harus muncul
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        } else {
            // Mobile: sidebar harus tersembunyi
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
        }
    });
    
    // Initial state
    if (window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
    } else {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
    }
});

// Animasi ripple tetap jalan
document.querySelectorAll('.btn-primary, .btn-danger').forEach(btn => {
    btn.addEventListener('click', function(e) {
        let x = e.clientX - e.target.getBoundingClientRect().left;
        let y = e.clientY - e.target.getBoundingClientRect().top;
        let ripple = document.createElement('span');
        ripple.style.position = 'absolute';
        ripple.style.width = '0px';
        ripple.style.height = '0px';
        ripple.style.backgroundColor = 'rgba(255,255,255,0.5)';
        ripple.style.borderRadius = '50%';
        ripple.style.transform = 'translate(-50%, -50%)';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.style.transition = 'width 0.6s, height 0.6s, opacity 0.6s';
        ripple.style.pointerEvents = 'none';
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.style.width = '300px';
            ripple.style.height = '300px';
            ripple.style.opacity = '0';
        }, 10);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});
</script>

</body>
</html>