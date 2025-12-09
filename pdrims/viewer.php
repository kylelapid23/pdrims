<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDRIMS - Resident Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#004D40', 
                        'primary-deep': '#00382E', 
                        'primary-light': '#00796B', 
                        'aid-color': '#A27500',    
                        'report-color': '#800000', 
                        'neutral-bg': '#F6FFF7', 
                        'aid-color-dark': '#886000',
                        'report-color-dark': '#6a0000',
                        'maroon-button': '#800000', 
                        'maroon-hover': '#6a0000'
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03)',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('disaster.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; 
        }
        .message-inbox::-webkit-scrollbar { width: 8px; }
        .message-inbox::-webkit-scrollbar-thumb {
            background-color: #00796B30; 
            border-radius: 4px;
        }
        .full-interface-modal-content {
            height: 95vh;
            max-width: 90%; 
            width: 100%;
        }
        @media (min-width: 1024px) {
            .full-interface-modal-content {
                max-width: 1000px; 
                height: 85vh;
            }
        }
        .message-tab-btn {
            cursor: pointer;
            padding: 10px 18px; 
            font-weight: 600;
            font-size: 0.95rem; 
            border: 1px solid transparent;
            border-bottom: none;
            transition: all 0.2s;
            white-space: nowrap;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        .message-tab-btn.active-tab {
            background: linear-gradient(to right, #004D40, #00796B); 
            color: white;
            border-color: #004D40; 
            position: relative;
            z-index: 20; 
            transform: translateY(0);
        }
        .message-tab-btn:not(.active-tab) {
            background-color: #E5E7EB; 
            color: #4B5563; 
            border-color: #D1D5DB; 
            border-bottom: 1px solid #D1D5DB;
            transform: translateY(1px); 
        }
        .message-tab-btn:not(.active-tab):hover {
            color: #004D40; 
            background-color: #F3F4F6; 
        }
        .message-item-container { transition: border-color 0.3s, box-shadow 0.3s; }
        .message-header { border-radius: 0.5rem; }
        .message-item-container:not(.border-gray-200) .message-header {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col bg-white/90">
        <header class="sticky top-0 z-40 bg-primary-dark shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex-shrink-0">
                        <span class="text-xl font-bold tracking-tight text-white">
                            PDRIMS <span class="text-white font-light">Viewer</span>
                        </span>
                    </div>

                    <nav class="hidden md:flex space-x-8"></nav>

                    <div class="relative">
                        <button type="button" id="profile-menu-button"
                                class="flex max-w-xs items-center rounded-full bg-primary-dark text-sm focus:outline-none"
                                aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">Open user menu</span>
                            
                            <div class="hidden md:flex flex-col items-end mr-3">
                                <span class="text-sm font-medium text-white" id="viewer-name-display">Loading...</span>
                                <span class="text-xs text-gray-300 font-normal" id="viewer-role-display">Resident</span>
                            </div>
                            
                            <div class="h-9 w-9 rounded-full bg-primary-light flex items-center justify-center text-white shadow-md">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                        </button>
                        
                        <div id="profile-dropdown" class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="profile-menu-button" tabindex="-1">
                            

                            
                            <a href="#" onclick="openLogoutModal(); return false;"
                               class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem" tabindex="-1">
                                <i data-lucide="log-out" class="w-4 h-4 inline-block mr-2"></i> Log Out
                            </a>
                        </div>
                    </div>

                    <button type="button" id="mobile-menu-button"
                            class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-200 hover:bg-primary-deep hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-light"
                            aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i data-lucide="menu" class="block h-6 w-6" id="menu-icon"></i>
                        <i data-lucide="x" class="hidden h-6 w-6" id="close-icon"></i>
                    </button>
                </div>
            </div>

            <div class="md:hidden hidden bg-white border-t border-gray-100" id="mobile-menu">
                <div class="space-y-1 px-2 pt-2 pb-3"></div>
                <div class="border-t border-gray-100 pt-4 pb-3">
                    <div class="mt-3 space-y-1 px-2">

                        <a href="#" onclick="openLogoutModal(); return false;"
                           class="block rounded-md px-3 py-2 text-base font-medium text-red-600 hover:bg-red-50">Log Out</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            
            <section id="page-dashboard" class="page-content">
                
                <div class="h-48 rounded-xl shadow-xl mb-8 bg-cover bg-center relative overflow-hidden" 
                     style="background-image: url('disasterdash.png');">
                </div>
                <div class="bg-gray-50 p-8 rounded-lg border border-gray-200 mb-6"> 
                    <h2 class="text-xl font-bold text-primary-dark mb-6">Key Metrics Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 transition duration-300 hover:shadow-lg">
                            <p class="text-sm font-medium text-gray-600">Affected Households Profiled</p>
                            <p class="text-4xl font-bold mt-2 rotate-1 text-red-600" id="stat-profiled-count">125</p>
                            <p class="text-xs text-gray-400 mt-1">Total unique records</p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 transition duration-300 hover:shadow-lg">
                            <p class="text-sm font-medium text-gray-600">Totally Destroyed Homes (100% Loss)</p>
                            <p class="text-4xl font-bold mt-2 text-yellow-600" id="stat-destroyed-count">15</p>
                            <p class="text-xs text-gray-400 mt-1">Needs verification</p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 transition duration-300 hover:shadow-lg">
                            <p class="text-sm font-medium text-gray-600">Priority Households</p>
                            <p class="text-4xl font-bold mt-2 text-orange-600" id="stat-priority-count">28</p>
                            <p class="text-xs text-gray-400 mt-1">Severe damage/Elderly/Infants</p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 transition duration-300 hover:shadow-lg">
                            <p class="text-sm font-medium text-gray-600">Average Aid Distribution Rate</p>
                            <p class="text-4xl font-bold mt-2 text-green-600" id="stat-recovery-percent">15%</p>
                            <p class="text-xs text-gray-400 mt-1">Based on total aid records vs households</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-8 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-bold text-primary-dark mb-6">Disaster Recovery Visualizations</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-100">
                            <h3 class="text-lg font-semibold text-primary-dark mb-4">Damage Status Distribution</h3>
                            <div class="h-64 flex flex-col justify-center items-center" id="damage-distribution-container">
                                <!-- Dynamic Content will be injected here -->
                                <p class="text-gray-400 text-sm">Loading damage data...</p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-100">
                            <h3 class="text-lg font-semibold text-primary-dark mb-4">Initial Needs Aid Fulfillment Rate</h3>
                            <div class="flex justify-center items-center h-48 relative">
                                <div class="w-40 h-40 relative">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 128 128">
                                        <!-- Reduced radius to 56 to fits inside 128x128 with stroke 12 (56+6=62 < 64) -->
                                        <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none" />
                                        <circle id="viewerAidCircle" cx="64" cy="64" r="56" stroke="#0d9488" stroke-width="12" fill="none" 
                                                class="transition-all duration-1000 ease-out" 
                                                stroke-dasharray="352" stroke-dashoffset="352" stroke-linecap="round" />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <p class="text-3xl font-bold text-teal-700" id="viewerAidPercent">0%</p>
                                        <p class="text-xs text-gray-500">Fulfilled</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center space-x-6 mt-4 text-sm">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-teal-600 rounded-full mr-2"></span>
                                    <span id="viewerAidNeedsMetLabel" class="text-gray-700">Needs Met</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-gray-200 rounded-full mr-2"></span>
                                    <span id="viewerAidRemainingLabel" class="text-gray-700">Remaining Need</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
            

        </main>
        
        <div id="toast-container" class="fixed top-20 right-5 z-50"></div>

    </div>

    <div class="fixed bottom-6 right-6 z-30">
        <button type="button" onclick="openMessagesInterfaceModal()"
                class="bg-report-color hover:bg-red-700 text-white p-4 rounded-full shadow-lg transition duration-150 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-report-color/50">
            <i data-lucide="message-square" class="w-6 h-6"></i>
            <span class="sr-only">Messages / Submit Concern</span>
        </button>
    </div>

    <div id="messages-interface-modal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target.id === 'messages-interface-modal') closeMessagesInterfaceModal()">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden full-interface-modal-content flex flex-col" onclick="event.stopPropagation()">
            
            <div class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200 sticky top-0 bg-white">
                <h2 class="text-2xl font-bold text-gray-900">Messages & Concerns</h2>
                <button onclick="closeMessagesInterfaceModal()" class="text-gray-400 hover:text-gray-600 transition p-2 rounded-full hover:bg-gray-100">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="flex-1 flex flex-col overflow-hidden">
                <nav class="bg-white px-4 sm:px-6 pt-2 sticky top-0 z-10">
                    <div class="flex space-x-2 relative z-10 -mb-px"> 
                        <button onclick="showMessagesTab('submit', this)" id="tab-submit" 
                                class="message-tab-btn active-tab whitespace-nowrap">
                            Submit a Concern
                        </button>
                        <button onclick="showMessagesTab('inbox', this)" id="tab-inbox" 
                                class="message-tab-btn whitespace-nowrap">
                            Submitted Concerns
                        </button>
                    </div>
                </nav>
                
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 border-t border-gray-200">
                    
                    <div id="tab-pane-submit" class="message-tab-content">
                        <div class="bg-white p-4 sm:p-6 rounded-xl border border-gray-100 shadow-sm">

                            <form onsubmit="submitConcern(event);">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

                                    <div class="col-span-2">
                                        <label for="modal-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                        <input type="text" id="modal-subject" class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-light focus:border-primary-light" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="modal-description" class="block text-sm font-medium text-gray-700 mb-1">Detailed Description</label>
                                    <textarea id="modal-description" rows="4" class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-light focus:border-primary-light" required></textarea>
                                </div>

                                <div class="mb-6">
                                    <label for="modal-purok" class="block text-sm font-medium text-gray-700 mb-1">Purok (Optional)</label>
                                    <select id="modal-purok" class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-light focus:border-primary-light">
                                        <option value="">Select Purok</option>
                                        <option value="1">Purok 1</option>
                                        <option value="2">Purok 2</option>
                                    </select>
                                </div>

                                <button type="submit" class="w-full bg-primary-light hover:bg-primary-dark text-white font-semibold py-3 rounded-md transition duration-150 ease-in-out shadow-md hover:shadow-lg">
                                    Submit Concern
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="tab-pane-inbox" class="message-tab-content hidden">
                        <div class="bg-white p-4 sm:p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div class="space-y-3 message-inbox max-h-[600px] overflow-y-auto pr-2" id="inbox-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>

    <div id="logout-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target.id === 'logout-modal') closeLogoutModal()">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-sm w-full" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="text-center">
                    <i data-lucide="log-out" class="w-10 h-10 mx-auto text-maroon-button"></i>
                    <h3 class="mt-2 text-lg leading-6 font-medium text-gray-900">Confirm Log Out</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to log out of your PDRIMS Viewer account?
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button type="button" onclick="closeLogoutModal()"
                        class="px-4 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 transition duration-150 ease-in-out shadow-sm">
                    Cancel
                </button>
                <button type="button" onclick="performLogout()"
                        class="px-4 py-2 text-sm font-medium rounded-md text-white bg-maroon-button hover:bg-maroon-hover transition duration-150 ease-in-out shadow-md">
                    Log Out
                </button>
            </div>
        </div>
    </div>
    <script>
        const staticMessages = [
            { id: 'msg-1', category: 'Aid Inquiry', subject: 'Follow-up on rice distribution', submittedDate: '10/18/2025', status: 'Acknowledged', description: 'I am following up on the scheduled rice distribution for Zone 5. Our family has not yet received our allocation. We have 5 members, including two elderly.', response: 'Your aid has been processed and is scheduled for pickup on Nov 10. Check your profile for the exact location and time slot. Please bring your QR code.', categoryColor: 'primary-light', statusColor: 'primary-light' },
            { id: 'msg-2', category: 'Unrecorded Damage', subject: 'My roof damage was missed by assessor', submittedDate: '10/15/2025', status: 'Acknowledged', description: 'The assessor only noted minor damage, but half of my roof is gone. The 75% loss assessment is wrong. I have photo evidence. This is a very long description to show that the container will expand to fit the content, and the whole modal area is scrollable. The issue is critical and needs immediate attention as the rainy season is approaching and our temporary shelter is inadequate. We need construction materials or a full re-evaluation.', response: 'Your concern has been forwarded to the assessment team. A re-evaluation is scheduled within 3 working days. You will be contacted by text message with the exact schedule.', categoryColor: 'aid-color', status: 'Acknowledged', statusColor: 'primary-light' },
            { id: 'msg-3', category: 'Profile Update', subject: "Typo in last name: 'Dela Cruz' vs 'De la Cruz'", submittedDate: '10/10/2025', status: 'Pending', description: 'My official ID uses "De la Cruz" with a space, but the PDRIMS profile has "Dela Cruz". Please fix this discrepancy.', response: 'Thank yourself for submitting your request. We are verifying the information against your submitted documents and will process the update within 5 working days.', categoryColor: 'blue-500', status: 'Pending', statusColor: 'gray-500' }
        ];
        
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            showPage('dashboard');
            updateDashboardMetrics(); // Fetch real data
            loadUserProfile(); // Fetch user name
            loadUserConcerns(); // Fetch submitted concerns
        });

        async function loadUserProfile() {
            try {
                // Assuming login sets this. Fallback to empty if not found.
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                
                if (!currentUser.id) {
                    console.warn('No logged in user found in localStorage');
                    return;
                }

                const res = await fetch(`api_get_viewer_profile.php?id=${currentUser.id}`);
                const data = await res.json();

                if (data.error) {
                    console.error('Error fetching profile:', data.error);
                    return;
                }

                const nameDisplay = document.getElementById('viewer-name-display');
                const roleDisplay = document.getElementById('viewer-role-display');

                if (nameDisplay) {
                    nameDisplay.textContent = `${data.firstName} ${data.surname}`;
                }

                if (roleDisplay) {
                    if (data.isMember) {
                        roleDisplay.textContent = 'Resident';
                        roleDisplay.classList.remove('hidden');
                    } else {
                        roleDisplay.classList.add('hidden');
                    }
                }

            } catch (err) {
                console.error('Error loading user profile:', err);
            }
        }

        async function updateDashboardMetrics() {
            try {
                const res = await fetch('api_dashboard_stats.php');
                const data = await res.json();
                
                if (data.error) {
                    console.error('Error fetching dashboard data:', data.error);
                    return;
                }

                // Update Key Metrics
                const setText = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val; };
                setText('stat-profiled-count', data.totalProfiled || 0);
                setText('stat-destroyed-count', data.totalDestroyed || 0);
                setText('stat-priority-count', data.totalPriority || 0);
                setText('stat-recovery-percent', `${data.recoveryPercent || 0}%`);

                // Update Aid Circle
                const percent = parseInt(data.recoveryPercent || 0);
                const circle = document.getElementById('viewerAidCircle');
                if (circle) {
                    const radius = 56; // Updated radius to match SVG
                    const circumference = 2 * Math.PI * radius; // ~352
                    const offset = circumference - (percent / 100) * circumference;
                    circle.style.strokeDasharray = circumference; // Ensure array matches new radius
                    circle.style.strokeDashoffset = offset;
                }
                const percentText = document.getElementById('viewerAidPercent');
                if(percentText) percentText.textContent = `${percent}%`;

                // Update Legend
                const metLabel = document.getElementById('viewerAidNeedsMetLabel');
                const remainingLabel = document.getElementById('viewerAidRemainingLabel');
                if(metLabel) metLabel.textContent = `Needs Met (${percent}%)`;
                if(remainingLabel) remainingLabel.textContent = `Remaining Need (${100 - percent}%)`;

                // Update Damage Distribution
                renderDamageDistribution(data.damageStats || {});

            } catch (err) {
                console.error('Error updating viewer dashboard:', err);
            }
        }

        function renderDamageDistribution(stats) {
            const container = document.getElementById('damage-distribution-container');
            if (!container) return;

            const total = Object.values(stats).reduce((a, b) => a + b, 0) || 1;
            const labels = {
                '100': { label: 'Total Loss (100%)', color: 'bg-red-600' },
                '75':  { label: 'Major Damage (75%)', color: 'bg-orange-600' },
                '50':  { label: 'Moderate Damage (50%)', color: 'bg-yellow-500' },
                '25':  { label: 'Minor Damage (25%)', color: 'bg-blue-500' },
                '0':   { label: 'No Damage (0%)', color: 'bg-green-500' }
            };

            // Sorted keys 100 -> 0
            const keys = ['100', '75', '50', '25', '0'];
            
            let html = '<div class="w-full space-y-3">';
            
            keys.forEach(key => {
                const count = stats[key] || 0;
                const meta = labels[key];
                const pct = Math.round((count / total) * 100);
                
                html += `
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">${meta.label}</span>
                            <span class="font-bold text-gray-900">${count} (${pct}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="${meta.color} h-2.5 rounded-full" style="width: ${pct}%"></div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function showPage(pageId, isMobile = false) {
            document.querySelectorAll('.page-content').forEach(page => page.classList.add('hidden'));
            document.getElementById(`page-${pageId}`).classList.remove('hidden');
            if (isMobile) {
                document.getElementById('mobile-menu').classList.add('hidden');
                document.getElementById('menu-icon').classList.remove('hidden');
                document.getElementById('close-icon').classList.add('hidden');
            }
            lucide.createIcons();
        }
        
        const profileButton = document.getElementById('profile-menu-button');
        const profileDropdown = document.getElementById('profile-dropdown');
        
        profileButton.addEventListener('click', () => { profileDropdown.classList.toggle('hidden'); });

        document.addEventListener('click', (event) => {
            if (!profileButton.contains(event.target) && !profileDropdown.contains(event.target) && !profileDropdown.classList.contains('hidden')) {
                profileDropdown.classList.add('hidden');
            }
        });

        const mobileButton = document.getElementById('mobile-menu-button');
        mobileButton.addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
            document.getElementById('menu-icon').classList.toggle('hidden');
            document.getElementById('close-icon').classList.toggle('hidden');
        });

        function showDataTab(tabId) {
            document.querySelectorAll('.data-tab-content').forEach(content => content.classList.add('hidden'));
            document.querySelectorAll('.message-tab-btn').forEach(btn => { btn.classList.remove('active-tab'); });

            document.getElementById(`data-tab-${tabId}`).classList.remove('hidden');
            document.getElementById(`tab-${tabId}`).classList.add('active-tab');
            lucide.createIcons();
        }
        
        let USER_CONCERNS = [];

        async function loadUserConcerns() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                if (!currentUser.id) return;

                const res = await fetch(`api_concerns.php?userId=${currentUser.id}`);
                const data = await res.json();
                
                if (data.success) {
                    USER_CONCERNS = data.concerns || [];
                    renderInboxList();
                }
            } catch (err) {
                console.error('Error loading concerns:', err);
            }
        }
        
        function renderInboxList() {
            const container = document.getElementById('inbox-list');
            if (!container) return;

            if (USER_CONCERNS.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-4">No submitted concerns yet.</p>';
                return;
            }

            container.innerHTML = USER_CONCERNS.map(msg => {
                const isResolved = msg.status === 'Resolved' || msg.status === 'Acknowledged';
                const displayStatus = isResolved ? 'Acknowledged' : msg.status;
                
                // Status Colors: Acknowledged = Dark Blue, Pending = Gray
                const statusColorClass = isResolved ? 'bg-blue-900 text-white' : 
                                         msg.status === 'Pending' ? 'bg-gray-500 text-white' : 'bg-primary-light text-white';
                
                const description_content = msg.description.trim(); 
                
                // Only show Admin Response if Acknowledged/Resolved
                let responseHtml = '';
                if (isResolved) {
                    const responseText = msg.response || "No response recorded.";
                    responseHtml = `
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-md text-sm">
                            <span class="font-bold text-blue-900 block mb-1">Admin Response:</span>
                            <p class="text-blue-800">${responseText}</p>
                        </div>`;
                }

                return `
                    <div id="msg-${msg.id}-container" class="message-item-container border border-gray-200 rounded-lg transition duration-150 ease-in-out overflow-hidden shadow-sm">
                        <div class="p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 flex flex-col space-y-1 message-header rounded-lg text-left" 
                             onclick="toggleMessageDetails('${msg.id}')">
                            <div class="flex justify-between items-center w-full mb-1">
                                <span class="text-xs font-semibold text-gray-500">ID: ${msg.id}</span>
                                <span class="px-3 py-0.5 text-xs font-medium rounded-full ${statusColorClass} flex-shrink-0">${displayStatus}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-800 truncate max-w-full">${msg.subject}</span>
                            <p class="text-xs text-gray-500 pt-1">Submitted: ${msg.created_at}</p>
                        </div>
                        <div id="msg-${msg.id}-details" class="p-4 pt-3 bg-white hidden">
                            <div class="space-y-4 text-left">
                                <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">${description_content}</div>
                                <div class="text-xs text-left text-gray-500 pt-2"><p>Purok: ${msg.purok || 'N/A'}</p></div>
                                ${responseHtml}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            lucide.createIcons();
        }
        
        function toggleMessageDetails(messageId) {
            const detailElement = document.getElementById(`msg-${messageId}-details`);
            const headerElement = document.querySelector(`#msg-${messageId}-container .message-header`);
            const containerElement = document.getElementById(`msg-${messageId}-container`);

            if (detailElement.classList.contains('hidden')) {
                detailElement.classList.remove('hidden');
                headerElement.classList.remove('rounded-b-lg'); 
                containerElement.classList.add('border-primary-dark', 'shadow-lg'); 
                containerElement.classList.remove('border-gray-200');
            } else {
                detailElement.classList.add('hidden');
                headerElement.classList.add('rounded-lg'); 
                containerElement.classList.remove('border-primary-dark', 'shadow-lg'); 
                containerElement.classList.add('border-gray-200');
            }
        }
        
        function showMessagesTab(tabId, element) {
            document.querySelectorAll('.message-tab-content').forEach(content => content.classList.add('hidden'));
            document.querySelectorAll('.message-tab-btn').forEach(btn => { btn.classList.remove('active-tab'); });

            document.getElementById(`tab-pane-${tabId}`).classList.remove('hidden');
            element.classList.add('active-tab');
            
            if (tabId === 'inbox') renderInboxList();
            
            const scrollContainer = document.querySelector('#messages-interface-modal .flex-1.overflow-y-auto');
            if (scrollContainer) scrollContainer.scrollTop = 0;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            let colorClass = type === 'error' ? 'bg-report-color' : 'bg-primary-light';
            let icon = type === 'error' ? 'x-circle' : 'check-circle';

            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 mb-4 text-white ${colorClass} rounded-lg shadow-soft transition-opacity duration-300 ease-in-out`;
            toast.style.opacity = 0; 
            toast.innerHTML = `<i data-lucide="${icon}" class="w-5 h-5 mr-2"></i><div class="text-sm font-medium">${message}</div>`;
            container.appendChild(toast);
            lucide.createIcons(); 

            setTimeout(() => { toast.style.opacity = 1; }, 10);
            setTimeout(() => {
                toast.style.opacity = 0;
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function openMessagesInterfaceModal() {
            const modal = document.getElementById('messages-interface-modal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
            setTimeout(() => { modal.style.opacity = 1; }, 10);
            lucide.createIcons();
            
            showMessagesTab('submit', document.getElementById('tab-submit'));
        }

        function closeMessagesInterfaceModal() {
            const modal = document.getElementById('messages-interface-modal');
            modal.style.opacity = 0;
            document.body.style.overflow = ''; 
            setTimeout(() => {
                modal.classList.add('hidden');
                document.querySelector('#messages-interface-modal form')?.reset(); 
            }, 300); 
        }
        
        async function submitConcern(event) {
            event.preventDefault(); 
            
            const subject = document.getElementById('modal-subject').value;
            const description = document.getElementById('modal-description').value;
            const purok = document.getElementById('modal-purok').value || '';
            
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            if (!currentUser.id) return showToast('Please log in first.', 'error');
            
            if (subject && description) {
                try {
                    const res = await fetch('api_concerns.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            userId: currentUser.id,
                            subject,
                            description,
                            purok
                        })
                    });
                    const result = await res.json();
                    
                    if (result.success) {
                        showToast(`Concern submitted successfully!`, 'success');
                        event.target.reset();
                        loadUserConcerns(); // Reload list
                        showMessagesTab('inbox', document.getElementById('tab-inbox')); // Switch to inbox tab
                    } else {
                        showToast(result.error || 'Failed to submit concern', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToast('Connection error', 'error');
                }
            } else {
                 showToast('Please fill in required fields.', 'error');
            }
        }

        // Functions for the Logout Modal
        function openLogoutModal() {
            const modal = document.getElementById('logout-modal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
            profileDropdown.classList.add('hidden');
            lucide.createIcons();
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logout-modal');
            modal.classList.add('hidden');
            document.body.style.overflow = ''; 
        }

        function performLogout() {
            closeLogoutModal();
            localStorage.removeItem('currentUser');
            showToast('You have been logged out.', 'error');
            setTimeout(() => {
                window.location.href = 'landing.php';
            }, 1000);
        }
    </script>
</body>
</html>