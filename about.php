<?php include "includes/header.php"; ?>

<section class="relative bg-gradient-to-b from-cyan-50 via-white to-white py-20 overflow-hidden">
    <div class="absolute inset-0 bg-hero-pattern opacity-5"></div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center mb-16">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-4 tracking-tight">About <span class="text-primary">MyBeachCare</span></h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto font-light">Dedicated to preserving Malaysia's coastal beauty through community action and innovation.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-20">
            <div class="relative group">
                <div class="absolute -inset-4 bg-gradient-to-r from-primary to-secondary rounded-xl opacity-30 group-hover:opacity-50 blur-lg transition duration-500"></div>
                <img src="https://images.unsplash.com/photo-1534008897995-27a23e859048?auto=format&fit=crop&q=80&w=800" alt="Beach Cleanup Team" class="relative rounded-2xl shadow-2xl transform group-hover:scale-[1.02] transition duration-500 z-10">
            </div>
            <div class="space-y-6">
                <h2 class="text-3xl font-bold text-gray-900">Our Mission</h2>
                <p class="text-gray-600 leading-relaxed text-lg">
                    MyBeachCare was founded by Dila Arifin and the team cooperated together to make a successful platform to build a community. It's not just about cleaning the beach but our goal is to build a community, social, and a place where people share their knowledge and experience.
                </p>

                <h2 class="text-3xl font-bold text-gray-900 pt-4">Our Vision</h2>
                <p class="text-gray-600 leading-relaxed text-lg">
                    We envision a future where every coastline in Malaysia is pristine, protected, and cherished by a proactive community of guardians. We strive to create a culture of environmental responsibility that transcends generations, ensuring our beaches remain vibrant ecosystems for all to enjoy.
                </p>
                
                <div class="flex items-center space-x-4 pt-4">
                    <div class="flex flex-col">
                        <span class="text-3xl font-bold text-primary">25+</span>
                        <span class="text-sm text-gray-500">Beaches Monitored</span>
                    </div>
                    <div class="h-10 w-px bg-gray-300"></div>
                    <div class="flex flex-col">
                        <span class="text-3xl font-bold text-primary">1k+</span>
                        <span class="text-sm text-gray-500">Volunteers</span>
                    </div>
                    <div class="h-10 w-px bg-gray-300"></div>
                    <div class="flex flex-col">
                        <span class="text-3xl font-bold text-primary">6+</span>
                        <span class="text-sm text-gray-500">Events</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-10 shadow-xl border border-gray-100">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Meet the Team</h2>
            <div class="flex flex-wrap justify-center gap-16">
                <!-- Team Member 1 -->
                <div class="text-center group">
                    <div class="relative w-32 h-32 mx-auto mb-4 rounded-full overflow-hidden border-4 border-white shadow-lg group-hover:border-primary transition duration-300">
                        <img src="img/admin1.png" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Nur Fadhilah</h3>
                    <p class="text-primary font-medium">Lead Developer</p>
                </div>
                <!-- Team Member 2 -->
                <div class="text-center group">
                    <div class="relative w-32 h-32 mx-auto mb-4 rounded-full overflow-hidden border-4 border-white shadow-lg group-hover:border-primary transition duration-300">
                        <img src="img/admin2.png" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Nurul Absarina</h3>
                    <p class="text-primary font-medium">System Admin</p>
                </div>
                <!-- Team Member 3 -->
                <div class="text-center group">
                    <div class="relative w-32 h-32 mx-auto mb-4 rounded-full overflow-hidden border-4 border-white shadow-lg group-hover:border-primary transition duration-300">
                        <img src="img/admin3.png" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Nur Alia</h3>
                    <p class="text-primary font-medium">UI/UX Designer</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
