<?php
include "includes/header.php";

// Fetch events
try {
    $query = "SELECT e.*, b.name as beach_name, b.state as beach_state, b.image as beach_image,
              u.full_name as organizer_name, l.full_name as leader_name,
              (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'Registered') as participant_count 
              FROM events e 
              JOIN beaches b ON e.beach_id = b.id 
              LEFT JOIN users u ON e.organizer_id = u.id
              LEFT JOIN users l ON e.leader_id = l.id
              ORDER BY e.event_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Split into Upcoming and Completed
    $upcoming_events = [];
    $completed_events = [];
    $now = date('Y-m-d H:i:s');

    foreach ($all_events as $event) {
        if ($event['event_date'] >= $now && $event['status'] != 'Completed') {
            $upcoming_events[] = $event;
        } else {
            $completed_events[] = $event;
        }
    }
    
    // Sort upcoming by ASC (closest first)
    usort($upcoming_events, function($a, $b) {
        return strtotime($a['event_date']) - strtotime($b['event_date']);
    });

    // If logged in, fetch user's registered events
    $user_registrations = [];
    if ($isLoggedIn) {
        $reg_stmt = $conn->prepare("SELECT event_id FROM event_participants WHERE user_id = :user_id");
        $reg_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_registrations = $reg_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-b from-orange-50 via-white to-white pb-12 pt-16 overflow-hidden">
        <div class="absolute inset-0 bg-hero-pattern opacity-5"></div>
        
        <div class="container mx-auto px-6 relative z-10 pt-8">
            <div class="flex flex-col items-center justify-center text-center space-y-6 mb-12">
                <div class="space-y-2">
                    <h2 class="text-5xl md:text-6xl font-outfit font-extrabold tracking-tight text-gray-900">
                        Our Cleanup <span class="text-primary italic">Events</span>
                    </h2>
                </div>
                <p class="max-w-xl text-lg text-gray-600 leading-relaxed font-light">
                    Be a part of the change. Join our community-led beach cleanups and help us preserve Malaysia's coastal beauty, one event at a time.
                </p>
            </div>
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mt-8 bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl flex items-center shadow-sm reveal mx-auto max-w-2xl">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-medium"><?= htmlspecialchars($_GET['success']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="mt-8 bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-2xl flex items-center shadow-sm reveal mx-auto max-w-2xl">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <span class="font-medium"><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Events Grid -->
    <main class="container mx-auto px-6 py-12">
        


        <!-- Upcoming Events Section -->
        <h3 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <span class="w-2 h-8 bg-primary rounded-full"></span>
            Upcoming Events
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-20">
            <?php if (!empty($upcoming_events)): ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <?php $isRegistered = in_array($event['id'], $user_registrations); ?>
                    <div class="event-card group bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm transition-all duration-300 card-hover reveal">
                        <!-- Image Container -->
                        <div class="relative h-48 overflow-hidden">
                            <?php
                                $display_image = !empty($event['image']) ? $event['image'] : (!empty($event['beach_image']) ? $event['beach_image'] : 'https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?auto=format&fit=crop&q=80&w=800');
                            ?>
                            <img src="<?= htmlspecialchars($display_image) ?>" 
                                alt="<?= htmlspecialchars($event['title']) ?>" 
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            
                            <!-- Status Badge -->
                            <div class="absolute top-4 right-4 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm bg-blue-500 text-white">
                                <?= $event['status'] ?>
                            </div>

                            <!-- Date Badge -->
                            <div class="absolute top-4 left-4 px-3 py-2 rounded-xl text-center text-white bg-primary/90 backdrop-blur-md shadow-lg">
                                <span class="block text-xs font-bold uppercase"><?= date('M', strtotime($event['event_date'])) ?></span>
                                <span class="block text-xl font-black"><?= date('d', strtotime($event['event_date'])) ?></span>
                            </div>

                            <!-- State Badge -->
                            <div class="absolute bottom-4 left-4 px-3 py-1 rounded-lg text-[10px] font-bold text-white bg-black/40 backdrop-blur-md uppercase tracking-wider">
                                <?= htmlspecialchars($event['beach_state'] ?? 'Malaysia') ?>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="p-6 space-y-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 group-hover:text-primary transition-colors"><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="flex items-center text-gray-400 text-sm mt-1">
                                    <i class="fas fa-location-dot mr-2"></i>
                                    <span><?= htmlspecialchars($event['beach_name']) ?></span>
                                </div>
                                <div class="flex items-center text-gray-400 text-sm mt-1">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span><?= date('g:i A', strtotime($event['event_date'])) ?></span>
                                </div>
                                <div class="flex items-center text-gray-400 text-sm mt-1">
                                    <i class="fas fa-users mr-2"></i>
                                    <span><?= number_format($event['participant_count']) ?> joined</span>
                                </div>
                                <div class="flex items-center text-amber-600 font-bold text-sm mt-1">
                                    <i class="fas fa-tag mr-2"></i>
                                    <span>RM<?= number_format($event['price'] ?? 25.00, 2) ?></span>
                                </div>
                            </div>
                            
                            <p class="text-gray-500 text-sm line-clamp-3">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>

                            <!-- Action -->
                            <div class="pt-4 flex flex-col gap-3">
                                <?php if ($isRegistered): ?>
                                    <button disabled class="w-full py-3 bg-emerald-100 text-emerald-700 font-bold rounded-2xl flex items-center justify-center space-x-2">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Registered</span>
                                    </button>
                                <?php else: ?>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button onclick="showEventDetails(<?= htmlspecialchars(json_encode($event)) ?>)" class="w-full py-3 bg-white border-2 border-primary/20 text-primary font-bold rounded-2xl hover:bg-primary/5 transition-all flex items-center justify-center gap-2">
                                            <i class="fas fa-circle-info"></i>
                                            <span>Details</span>
                                        </button>
                                        <button onclick="confirmRegistration(<?= $event['id'] ?>, <?= htmlspecialchars($event['price'] ?? 25.00) ?>)" class="w-full py-3 bg-primary text-white font-bold rounded-2xl hover:bg-black transition-all duration-300 shadow-lg shadow-primary/20 flex items-center justify-center space-x-2 group">
                                            <span>Register</span>
                                            <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 font-medium">No upcoming events at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Events Section -->
        <h3 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
            Completed Events
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (!empty($completed_events)): ?>
                <?php foreach ($completed_events as $event): ?>
                    <div class="event-card group bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm opacity-90 hover:opacity-100 transition-all reveal">
                        <!-- Image Container -->
                        <div class="relative h-40 overflow-hidden grayscale group-hover:grayscale-0 transition-all duration-700">
                            <?php
                                $display_image = !empty($event['image']) ? $event['image'] : (!empty($event['beach_image']) ? $event['beach_image'] : 'https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?auto=format&fit=crop&q=80&w=800');
                            ?>
                            <img src="<?= htmlspecialchars($display_image) ?>" class="w-full h-full object-cover">
                            
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <span class="px-4 py-2 bg-emerald-500 text-white text-xs font-black uppercase tracking-widest rounded-full shadow-lg">COMPLETED</span>
                            </div>
                        </div>

                        <div class="p-6">
                            <h4 class="font-bold text-gray-900 mb-2"><?= htmlspecialchars($event['title']) ?></h4>
                            <div class="flex items-center text-xs text-gray-400 mb-4">
                                <i class="fas fa-calendar-check mr-2"></i>
                                <span>Performed on <?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                            </div>
                            <button onclick="showEventDetails(<?= htmlspecialchars(json_encode($event)) ?>, true)" class="w-full py-2.5 bg-gray-100 text-gray-500 font-bold rounded-xl hover:bg-gray-200 transition-all text-xs uppercase tracking-wider">
                                View Summary
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 font-medium">No completed events to show.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showEventDetails(eventData, isPast = false) {
            const pDetails = eventData.provision_details && eventData.provision_details.trim() !== '' ? eventData.provision_details : 'Food & Drinks Included';
            const kDetails = eventData.kit_details && eventData.kit_details.trim() !== '' ? eventData.kit_details : 'T-Shirt Provided';
            const pImage = eventData.provision_image || 'img/food.png';
            const kImage = eventData.kit_image || 'img/mybeachcareshirt.png';

            Swal.fire({
                title: `<span class="font-outfit font-black text-2xl">${eventData.title}</span>`,
                html: `
                    <div class="text-left space-y-4 p-2">
                        <div class="bg-primary/5 p-4 rounded-2xl border border-primary/10">
                            <h4 class="font-bold text-primary text-sm uppercase tracking-wider mb-2">Event Highlights</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">${eventData.description}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Provisions -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col items-center text-center group/icon">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Provisions</span>
                                <div class="h-24 flex items-center justify-center mb-2">
                                    <img src="${pImage}" class="w-24 h-24 object-contain drop-shadow-sm group-hover/icon:scale-110 transition-transform duration-300" onerror="this.src='img/food.png'">
                                </div>
                                <span class="text-sm font-bold text-gray-700">${pDetails}</span>
                            </div>
                            <!-- Kit -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col items-center text-center group/icon">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Kit</span>
                                <div class="h-24 flex items-center justify-center mb-2">
                                    <img src="${kImage}" class="w-24 h-24 object-contain drop-shadow-sm group-hover/icon:scale-110 transition-transform duration-300" onerror="this.src='img/mybeachcareshirt.png'">
                                </div>
                                <span class="text-sm font-bold text-gray-700">${kDetails}</span>
                            </div>
                        </div>
                        <div class="bg-amber-50 p-4 rounded-xl border border-amber-100 flex items-start gap-3">
                            <i class="fas fa-hand-holding-dollar text-amber-500 mt-1"></i>
                            <div>
                                <span class="block text-sm font-bold text-amber-700 uppercase">Registration Fee: RM${parseFloat(eventData.price || 25).toFixed(2)}</span>
                                <p class="text-[10px] text-amber-600 font-medium">Funds used for cleaning tools, banners, and disposal bags.</p>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: isPast ? 'Close' : 'Register Now',
                showConfirmButton: !isPast,
                cancelButtonText: isPast ? 'Back' : 'Close',
                confirmButtonColor: '#512DA8',
                customClass: {
                    popup: 'rounded-[2.5rem]',
                    confirmButton: 'rounded-full px-8 py-3',
                    cancelButton: 'rounded-full px-8 py-3'
                }
            }).then((result) => {
                if (result.isConfirmed && !isPast) {
                    confirmRegistration(eventData.id, eventData.price || 25);
                }
            });
        }

        async function confirmRegistration(eventId, price) {
            <?php if (!$isLoggedIn): ?>
                checkVolunteerAuth(new Event('click'));
                return;
            <?php endif; ?>

            const formattedPrice = parseFloat(price).toFixed(2);
            
            const result = await Swal.fire({
                title: 'Confirm Registration',
                text: `A registration fee of RM${formattedPrice} is required to be paid at the counter on the event day. Do you agree to participate?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#512DA8',
                confirmButtonText: 'Yes, I agree',
                cancelButtonText: 'Not now',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-full px-8 py-3',
                    cancelButton: 'rounded-full px-8 py-3'
                }
            });

            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'register_event.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'event_id';
                input.value = eventId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

<?php include "includes/footer.php"; ?>
