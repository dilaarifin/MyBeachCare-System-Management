    <footer class="bg-white border-t border-gray-100 py-12">
        <div class="container mx-auto px-6 text-center">
            <div class="flex flex-col items-center justify-center space-y-3 mb-6">
                <div class="flex items-center space-x-4">
                    <img src="img/logo.png" alt="My Beach Care Logo" class="h-10 w-auto opacity-80 hover:opacity-100 transition-opacity">
                    <img src="img/logo_uitm.png" alt="UiTM Logo" class="h-10 w-auto opacity-80 hover:opacity-100 transition-opacity">
                </div>
                <span class="font-outfit font-bold text-xl">MyBeachCare</span>
            </div>
            <p class="text-gray-500 text-sm">© 2025 MyBeachCare. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function checkVolunteerAuth(event) {
            <?php if (!isset($_SESSION['user_id'])): ?>
            event.preventDefault();
            Swal.fire({
                title: 'Join Our Community',
                text: 'To participate and track your impact, please sign in or create an account.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Log In',
                cancelButtonText: 'Maybe later',
                showDenyButton: true,
                denyButtonText: 'Sign Up',
                confirmButtonColor: '#512DA8',
                denyButtonColor: '#5C6BC0',
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-full px-6 py-2',
                    denyButton: 'rounded-full px-6 py-2',
                    cancelButton: 'rounded-full px-6 py-2'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                } else if (result.isDenied) {
                    window.location.href = 'signup.php';
                }
            });
            <?php endif; ?>
        }
    </script>
</body>
</html>
