<?php
session_start();
include "includes/db_conn.php";

$message_sent = false;
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            $message_sent = true;
        } catch (PDOException $e) {
            $error_msg = "Something went wrong. Please try again later.";
        }
    }
}
?>
<?php include "includes/header.php"; ?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-cyan-900 via-blue-900 to-indigo-900 py-20 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
    <div class="absolute -bottom-1 left-0 w-full overflow-hidden leading-none rotate-180">
        <svg class="relative block w-[calc(100%+1.3px)] h-[60px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-slate-50"></path>
        </svg>
    </div>
    
    <div class="container mx-auto px-6 relative z-10 text-center">
        <h1 class="text-4xl md:text-6xl font-outfit font-bold text-white mb-4 animate-fade-in-down">
            Get in <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-emerald-400">Touch</span>
        </h1>
        <p class="text-xl text-cyan-100 max-w-2xl mx-auto font-light leading-relaxed">
            Have questions about our beach cleanup initiatives? We'd love to hear from you.
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="max-w-7xl mx-auto px-6 py-16 -mt-20 relative z-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        <!-- Contact Information Card -->
        <div class="bg-gradient-to-br from-blue-800 to-cyan-900 rounded-3xl p-10 text-white shadow-2xl flex flex-col justify-between transform transition-transform hover:scale-[1.01] duration-300">
            <div>
                <h3 class="text-3xl font-bold font-outfit mb-8">Contact Information</h3>
                <p class="text-cyan-100 text-lg mb-10 leading-relaxed">
                    Fill out the form and our team will get back to you within 24 hours. Join us in making Malaysia's beaches cleaner and safer.
                </p>
                
                <div class="space-y-8">
                    <div class="flex items-start gap-6 group">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <p class="text-cyan-300 text-sm font-semibold uppercase tracking-wider mb-1">Phone</p>
                            <p class="text-xl font-medium">+6011-13217680</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-6 group">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-cyan-300 text-sm font-semibold uppercase tracking-wider mb-1">Email</p>
                            <p class="text-xl font-medium">hello@mybeachcare.org.my</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-6 group">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <p class="text-cyan-300 text-sm font-semibold uppercase tracking-wider mb-1">Headquarters</p>
                            <p class="text-xl font-medium">UiTM Kelantan, Kampung Belukar, 18500<br>Machang, Kelantan, Malaysia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="bg-white rounded-3xl p-10 shadow-xl border border-gray-100">
            <?php if ($message_sent): ?>
                <div class="h-full flex flex-col items-center justify-center text-center py-10">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center text-green-500 text-4xl mb-6 animate-bounce">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Message Sent!</h3>
                    <p class="text-gray-500 max-w-xs mx-auto mb-8">Thank you for contacting us. We will get back to you shortly.</p>
                    <a href="contact.php" class="px-8 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all shadow-lg hover:shadow-xl">Send Another Message</a>
                </div>
            <?php else: ?>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Send us a Message</h3>
                <p class="text-gray-500 mb-8">We'd love to hear your thoughts, suggestions, or reports.</p>

                <?php if ($error_msg): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r text-red-700">
                        <p class="font-medium"><?= htmlspecialchars($error_msg) ?></p>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="name" class="text-sm font-semibold text-gray-700 ml-1">Your Name</label>
                            <input type="text" id="name" name="name" 
                                class="w-full px-5 py-3 rounded-xl bg-gray-50 border-2 border-gray-100 focus:border-cyan-500 focus:bg-white focus:outline-none transition-all" 
                                placeholder="John Doe" required>
                        </div>
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-semibold text-gray-700 ml-1">Your Email</label>
                            <input type="email" id="email" name="email" 
                                class="w-full px-5 py-3 rounded-xl bg-gray-50 border-2 border-gray-100 focus:border-cyan-500 focus:bg-white focus:outline-none transition-all" 
                                placeholder="john@example.com" required>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="subject" class="text-sm font-semibold text-gray-700 ml-1">Subject</label>
                        <input type="text" id="subject" name="subject" 
                            class="w-full px-5 py-3 rounded-xl bg-gray-50 border-2 border-gray-100 focus:border-cyan-500 focus:bg-white focus:outline-none transition-all" 
                            placeholder="How can we help?" required>
                    </div>

                    <div class="space-y-2">
                        <label for="message" class="text-sm font-semibold text-gray-700 ml-1">Message</label>
                        <textarea id="message" name="message" rows="5" 
                            class="w-full px-5 py-3 rounded-xl bg-gray-50 border-2 border-gray-100 focus:border-cyan-500 focus:bg-white focus:outline-none transition-all resize-none" 
                            placeholder="Your message here..." required></textarea>
                    </div>

                    <button type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-cyan-500/50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                        Send Message <i class="fas fa-paper-plane ml-2"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
