<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDRIMS - Barangay Recovery System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#004D40',
                        'primary-deep': '#00382E',
                        'primary-light': '#00796B',
                        'aid-color': '#A27500',
                        'aid-color-dark': '#886000',
                        'report-color': '#800000',
                        'report-color-dark': '#6a0000',
                        'mgm-dark': '#800000',
                        'mgm-deep': '#630000'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F6FFF7;
        }

        .w-70 {
             width: 280px;
        }
        
        /* Custom CSS for the right-side drawer */
        .drawer {
            transition: transform 0.3s ease-out;
            transform: translateX(100%);
        }
        .drawer-open {
            transform: translateX(0);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 500;
            color: #E0E7FF;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background-color: rgba(0, 121, 107, 0.5);
        }
        .nav-link.active {
            background-color: #00796B;
            font-weight: 600;
            color: white;
        }
        .nav-link svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
        }
        .stat-card {
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            position: relative;
            min-height: 120px;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }
        .required::after {
            content: ' *';
            color: #ef4444;
        }
    </style>
   
    <script type="module">
       
        let isSidebarOpen = false;

        // --- MODAL & NOTIFICATION FUNCTIONS ---
        let modalClearTimeout = null;
        
        window.showModal = (contentHTML, maxWidthClass = 'max-w-xl') => {
            // Cancel any pending clear from hideModal
            if (modalClearTimeout) {
                clearTimeout(modalClearTimeout);
                modalClearTimeout = null;
            }
            
            const universalModal = document.getElementById('universalModal');
            const modalBody = document.getElementById('modalBody');
            const modalContentWrapper = document.getElementById('modalContentWrapper');
            
            modalBody.innerHTML = contentHTML;
            document.body.classList.add('overflow-hidden'); 

            const maxWClasses = ['max-w-xl', 'max-w-3xl', 'max-w-4xl', 'max-w-2xl', 'max-w-lg', 'max-w-md']; 
            if (modalContentWrapper) {
                maxWClasses.forEach(cls => modalContentWrapper.classList.remove(cls));
                modalContentWrapper.classList.add(maxWidthClass);
            }
            universalModal.classList.remove('invisible', 'opacity-0');
            universalModal.classList.add('visible', 'opacity-100');
            lucide.createIcons(); 
        };

        window.hideModal = () => {
             const universalModal = document.getElementById('universalModal');
             const modalBody = document.getElementById('modalBody');

            document.body.classList.remove('overflow-hidden');
            universalModal.classList.remove('visible', 'opacity-100');
            universalModal.classList.add('invisible', 'opacity-0');
            modalClearTimeout = setTimeout(() => { modalBody.innerHTML = ''; }, 300);
        };

        window.alertMessage = (message, type = 'info') => {
            const colors = {
                info: { bg: 'bg-green-50', border: 'border-green-500', text: 'text-green-800', title: 'Success' },
                error: { bg: 'bg-red-50', border: 'border-red-500', text: 'text-red-800', title: 'Error' }
            };
            const style = colors[type] || colors.info;
            showModal(`
                <h2 class="text-2xl font-bold ${style.text} mb-2">${style.title}</h2>
                <p class="text-sm text-gray-500 mb-4">System Message:</p>
                <div class="p-4 ${style.bg} rounded-lg border-l-4 ${style.border}">
                    <p class="${style.text}">${message}</p>
                </div>
                <button onclick="hideModal()" class="mt-6 w-full bg-primary-dark text-white font-semibold py-2 rounded-lg hover:bg-primary-deep transition">
                    OK
                </button>
            `, 'max-w-md');
        };

        // Logout function
        // Logout function
        window.confirmLogout = () => {
             showModal(`
                <h2 class="text-2xl font-bold text-mgm-dark mb-2">Confirm Logout</h2>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to sign out of the system?</p>
                <div class="flex gap-3 mt-6">
                    <button onclick="hideModal()" class="flex-1 bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button onclick="performLogout()" class="flex-1 bg-mgm-dark text-white font-semibold py-2 rounded-lg hover:bg-mgm-deep transition shadow-md">
                        Logout
                    </button> 
                </div>
            `, 'max-w-md');
        };

        window.performLogout = () => {
            localStorage.removeItem('currentUser');
            window.location.href = 'landing.php';
        };

        // --- Data Initialization ---
        // --- Data Initialization ---
        let HOUSEHOLDS_DATA = [];

        window.fetchHouseholds = async () => {
             const tableBody = document.getElementById('householdTableBody');
             if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">Loading data...</td></tr>';
             
             try {
                const res = await fetch('api_households.php');
                const data = await res.json();
                HOUSEHOLDS_DATA = data.map(h => ({
                    ...h,
                    // Use the full_name computed by the backend
                    headName: h.full_name || 'N/A',
                    // Use real members from API, default to empty array if none
                    familyMembers: h.members || [],
                    damageStatus: h.damage_status,
                    headAge: h.head_age,
                    contactNumber: h.contact_number,
                    initialNeeds: h.initial_needs, // Map snake_case from DB to camelCase for JS
                    id: String(h.id).padStart(3, '0') // Pad ID for display
                }));
                filterAndSearchHouseholdList();
                populateHouseholdDropdown(); // Populate Aid Distribution dropdown with fetched data
                updateDashboardMetrics(); // Update stats based on real data
             } catch (err) {
                 console.error('Error loading households:', err);
                 if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-red-500">Error loading data.</td></tr>';
             }
        };

        // Update user ID and role display
        const updateUserIdDisplay = () => {
            const userIdElement = document.getElementById('userIdDisplay');
            const roleElement = document.getElementById('userRoleDisplay');
            
            if (userIdElement || roleElement) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('currentUser') || '{}');
                    
                    if (userIdElement) {
                        const userId = currentUser.id || 'N/A';
                        userIdElement.innerHTML = `User ID: <strong class="text-white">off_${userId}</strong>`;
                    }
                    
                    if (roleElement) {
                        // Always use originalRole from database (the actual role column value from officials table)
                        // This will be "Barangay Captain", "System Administrator", "Barangay Official", etc.
                        let role = currentUser.originalRole;
                        
                        // If originalRole is not available (old session before API update), 
                        // user needs to log out and log back in to get the correct role from database
                        if (!role || role === 'official' || role === 'admin') {
                            // If we only have the mapped role, we can't show the actual database role
                            // User should log out and log back in to refresh their session
                            if (currentUser.role === 'admin') {
                                role = 'System Administrator'; // Likely correct
                            } else {
                                // Can't determine exact role without originalRole, show generic
                                role = 'Barangay Official';
                                console.warn('originalRole not found. User should log out and log back in to see correct role.');
                            }
                        }
                        
                        roleElement.innerHTML = role;
                    }
                } catch (error) {
                    console.error('Error reading user data:', error);
                    if (userIdElement) userIdElement.textContent = `User ID: N/A`;
                    if (roleElement) roleElement.textContent = 'N/A';
                }
            }
        };

        // System Logging Helper
        const logSystemAction = (action, target) => {
             try {
                const currentUser = JSON.parse(sessionStorage.getItem('currentUser') || '{}');
                const userId = currentUser.id ? `off_${currentUser.id}` : 'Unknown';
                const userName = currentUser.name || 'Barangay Official';

                fetch('api_logs.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({
                         user_id: userId,
                         user_name: userName,
                         action: action,
                         target: target
                     })
                }).catch(err => console.error('Error logging action:', err));
             } catch (e) {
                 console.error('Logging failed:', e);
             }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            fetchHouseholds();
            fetchAidRecords();
            updateUserIdDisplay();
        });
       
        // --- Aid Records Data Initialization ---
        let AID_RECORDS_DATA = [];

        window.fetchAidRecords = async () => {
            const tableBody = document.getElementById('aidRecordTableBody');
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">Loading aid distribution data...</td></tr>';
            
            try {
                const res = await fetch('api_aid_records.php');
                const data = await res.json();
                AID_RECORDS_DATA = data.map(r => ({
                    ...r,
                    // Map snake_case from DB to camelCase for JS compatibility
                    aidRecipientId: String(r.household_id).padStart(3, '0'),
                    aidType: r.aid_type,
                    dateDistributed: r.date_distributed,
                    distributedBy: r.distributed_by,
                    distributionNotes: r.notes,
                    recipientName: r.recipient_name || 'Unknown'
                }));
                filterAndSearchAidList();
            } catch (err) {
                console.error('Error loading aid records:', err);
                if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-red-500">Error loading aid data.</td></tr>';
            }
        };

        const updateDashboardMetrics = async () => {
            try {
                const res = await fetch('api_dashboard_stats.php');
                const data = await res.json();
                
                if (data.error) {
                    console.error('Error fetching dashboard data:', data.error);
                    return;
                }

                // Store data globally for reports
                window.LATEST_DASHBOARD_DATA = data;

                // Update key metrics using new API keys
                document.getElementById('stat-profiled-count').textContent = data.totalProfiled || 0;
                document.getElementById('stat-destroyed-count').textContent = data.totalDestroyed || 0;
                document.getElementById('stat-priority-count').textContent = data.totalPriority || 0;
                document.getElementById('stat-recovery-percent').textContent = `${data.recoveryPercent || 0}%`;

                // Update purok breakdown chart (key is purokStats)
                renderDamageDistribution(data.damageStats || {});

                // Update aid fulfillment rate chart (key is recoveryPercent)
                updateAidFulfillmentChart(data.recoveryPercent || 0);
                generateAISuggestions(data);
            } catch (error) {
                console.error('Error updating dashboard metrics:', error);
            }
        };

        const renderDamageDistribution = (stats) => {
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
        };

        const updateAidFulfillmentChart = (fulfillmentRate) => {
            const rateElement = document.getElementById('aidFulfillmentRate');
            const rateTextElement = document.getElementById('aidFulfillmentRateText');
            const remainingTextElement = document.getElementById('aidFulfillmentRemainingText');
            
            if (rateElement) {
                rateElement.textContent = `${fulfillmentRate}%`;
            }
            
            if (rateTextElement) {
                rateTextElement.textContent = `Needs Met (${fulfillmentRate}%)`;
            }
            
            if (remainingTextElement) {
                const remaining = 100 - fulfillmentRate;
                remainingTextElement.textContent = `Remaining Need (${remaining}%)`;
            }

            // Update the circular progress indicator
            const progressCircle = document.getElementById('aidFulfillmentCircle');
            if (progressCircle) {
                const radius = 60;
                const circumference = 2 * Math.PI * radius; // ≈ 377
                const offset = circumference - (fulfillmentRate / 100) * circumference;
                progressCircle.setAttribute('stroke-dasharray', circumference);
                progressCircle.setAttribute('stroke-dashoffset', offset);
            }
        };

        const generateAISuggestions = (data) => {
            const container = document.getElementById('aiSuggestionsContainer');
            if (!container) return;

            const suggestions = [];
            const totalProfiled = data.totalProfiled || 0;
            const totalDestroyed = data.totalDestroyed || 0;
            const totalPriority = data.totalPriority || 0;
            const recoveryPercent = data.recoveryPercent || 0;
            const damageStats = data.damageStats || {};

            // Priority-based suggestions
            if (totalPriority > 0) {
                const priorityPercent = Math.round((totalPriority / totalProfiled) * 100);
                suggestions.push({
                    type: 'critical',
                    icon: 'alert-triangle',
                    title: 'High Priority Households Detected',
                    text: `${totalPriority} household(s) (${priorityPercent}% of profiled) have ≥75% damage. Prioritize immediate relief distribution and temporary shelter coordination for these families.`
                });
            }

            // Recovery rate suggestions
            if (recoveryPercent < 25) {
                suggestions.push({
                    type: 'warning',
                    icon: 'trending-up',
                    title: 'Low Aid Fulfillment Rate',
                    text: `Current aid distribution is at ${recoveryPercent}%. Consider accelerating relief operations and coordinating with external agencies for additional resources.`
                });
            } else if (recoveryPercent >= 75) {
                suggestions.push({
                    type: 'success',
                    icon: 'check-circle',
                    title: 'Strong Recovery Progress',
                    text: `Aid fulfillment is at ${recoveryPercent}%. Focus on reaching the remaining households and begin planning for long-term rehabilitation programs.`
                });
            }

            // Damage distribution analysis
            const totalLoss = damageStats['100'] || 0;
            const majorDamage = damageStats['75'] || 0;
            if (totalLoss > 0) {
                suggestions.push({
                    type: 'info',
                    icon: 'home',
                    title: 'Housing Reconstruction Needed',
                    text: `${totalLoss} household(s) reported total loss (100% damage). Coordinate with DSWD and housing agencies for reconstruction assistance and temporary shelter programs.`
                });
            }

            // General recommendations based on data volume
            if (totalProfiled === 0) {
                suggestions.push({
                    type: 'info',
                    icon: 'clipboard-list',
                    title: 'Begin Household Profiling',
                    text: 'No households have been profiled yet. Start by conducting damage assessments and registering affected families in the system.'
                });
            } else if (totalProfiled > 0 && recoveryPercent === 0) {
                suggestions.push({
                    type: 'warning',
                    icon: 'package',
                    title: 'Initiate Aid Distribution',
                    text: `${totalProfiled} households are profiled but no aid has been distributed yet. Begin relief operations immediately.`
                });
            }

            // Minor damage focus
            const minorDamage = (damageStats['25'] || 0) + (damageStats['0'] || 0);
            if (minorDamage > 0 && totalPriority > 0) {
                suggestions.push({
                    type: 'info',
                    icon: 'users',
                    title: 'Resource Allocation Strategy',
                    text: `Consider prioritizing the ${totalPriority} high-priority households first. The ${minorDamage} households with minor/no damage may require less immediate attention.`
                });
            }

            // Render suggestions
            if (suggestions.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm italic">No specific recommendations at this time. Data looks balanced.</p>';
                return;
            }

            const typeStyles = {
                critical: 'bg-red-50 border-red-300 text-red-800',
                warning: 'bg-yellow-50 border-yellow-300 text-yellow-800',
                success: 'bg-green-50 border-green-300 text-green-800',
                info: 'bg-blue-50 border-blue-300 text-blue-800'
            };
            const iconColors = {
                critical: 'text-red-500',
                warning: 'text-yellow-600',
                success: 'text-green-500',
                info: 'text-blue-500'
            };

            container.innerHTML = suggestions.map(s => `
                <div class="flex items-start p-4 rounded-lg border ${typeStyles[s.type]} transition hover:shadow-md">
                    <i data-lucide="${s.icon}" class="w-5 h-5 ${iconColors[s.type]} mr-3 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <h4 class="font-semibold text-sm">${s.title}</h4>
                        <p class="text-sm mt-1 opacity-90">${s.text}</p>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        };
       
        // --- UTILITY FUNCTION ---
        const setText = (id, content) => {
            const el = document.getElementById(id);
            if (el) el.textContent = content;
        };

        // --- HOUSEHOLD DRAWER FUNCTIONS ---

        window.closeDetailsDrawer = () => {
            document.getElementById('detailsDrawer').classList.remove('drawer-open');
        };

        window.openDetailsDrawer = (householdId) => {
            const household = HOUSEHOLDS_DATA.find(h => h.id === householdId);
            if (!household) return alert('Data not found for ID: ' + householdId);

            const damageStatusMap = {
                '0': '0% - Unaffected/No Damage', '25': '25% - Minor Damage (Superficial)', 
                '50': '50% - Moderate Damage (Half Loss)', '75': '75% - Major Damage (Structural Loss)', 
                '100': '100% - Total Loss/Destroyed'
            };
            const damageLabel = damageStatusMap[household.damageStatus] || 'N/A';
           
            setText('drawerHeadName', household.headName || 'Unnamed Head');
            setText('drawerAge', household.headAge || 'N/A');
            setText('drawerContact', household.contactNumber || 'N/A');
            setText('drawerId', `ID: ${household.id}`);
            setText('drawerPurok', household.purok || 'N/A');
            setText('drawerDamageStatus', damageLabel);
            setText('drawerNeeds', household.initialNeeds || 'None specified');
            
            const memberList = household.familyMembers || [];
            setText('drawerMemberCount', memberList.length);
            const memberHTML = memberList.length > 0
                ? memberList.map((m, i) => {
                    const fullName = m.surname && m.first_name 
                        ? `${m.surname}, ${m.first_name} ${m.middle_initial || ''}`.trim() 
                        : `Member ${i + 1} (Name missing)`;
                    return `<li class="text-gray-700">${fullName} <span class="text-xs text-gray-500">(${m.relationship || 'Member'})</span></li>`;
                }).join('')
                : '<li class="text-gray-500">Head is only registered member.</li>';
            document.getElementById('drawerMemberList').innerHTML = memberHTML;

            // --- Aid Distribution History Logic ---
			
            const aidRecords = AID_RECORDS_DATA.filter(record => record.aidRecipientId === householdId);
            setText('drawerAidCount', aidRecords.length);

            const aidList = document.getElementById('drawerAidList');
            if (aidRecords.length > 0) {
                const aidHTML = aidRecords.map(record => `
                    <li class="p-2 bg-gray-50 rounded-md">
                        <span class="font-medium text-gray-800">${record.aidType}</span>: ${record.quantity}
                        <span class="text-xs text-gray-500 block">Date: ${record.dateDistributed}</span>
                    </li>
                `).join('');
                aidList.innerHTML = aidHTML;
            } else {
                aidList.innerHTML = '<li class="text-gray-500 p-2">No recorded aid distribution yet.</li>';
            }

            document.getElementById('drawerEditButton').setAttribute('onclick', `startEditProfile('${household.id}')`);
            document.getElementById('drawerDeleteButton').setAttribute('onclick', `confirmDeleteHousehold('${household.id}')`);
            document.getElementById('detailsDrawer').classList.add('drawer-open');
            
            closeAidDetailsDrawer();
            lucide.createIcons();
        };

        // Confirm and delete household
        window.confirmDeleteHousehold = (householdId) => {
            const household = HOUSEHOLDS_DATA.find(h => h.id === householdId);
            const headName = household ? household.headName : 'Unknown';
            
            showModal(`
                <h2 class="text-2xl font-bold text-mgm-dark mb-2">Delete Household?</h2>
                <p class="text-sm text-gray-500 mb-4">This action cannot be undone.</p>
                <div class="p-4 bg-gray-50 rounded-lg border-l-4 border-mgm-dark mb-4">
                    <p class="text-mgm-dark">You are about to permanently delete the household record for <strong>${headName}</strong> (ID: ${householdId}), including all family members and aid distribution records.</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="hideModal()" class="flex-1 bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button onclick="deleteHousehold('${householdId}')" class="flex-1 bg-mgm-dark text-white font-semibold py-2 rounded-lg hover:bg-mgm-deep transition">
                        Delete
                    </button>
                </div>
            `, 'max-w-md');
        };

        window.deleteHousehold = async (householdId) => {
            hideModal();
            
            try {
                const response = await fetch('api_households.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: householdId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeDetailsDrawer();
                    fetchHouseholds(); // Refresh the list
                    fetchAidRecords(); // Refresh aid records too
                    updateDashboardMetrics(); // Update dashboard stats
                    // Delay showing popup to let previous modal close properly
                    setTimeout(() => {
                        alertMessage('Household deleted successfully!', 'info');
                    }, 350);
                } else {
                    throw new Error(result.error || 'Unknown error');
                }
            } catch (error) {
                setTimeout(() => {
                    alertMessage('Error deleting household: ' + error.message, 'error');
                }, 350);
            }
        };

        window.startEditProfile = (householdId) => {
            closeDetailsDrawer();
            
            const profileLink = document.querySelector('[data-target="HouseholdProfiles"]');
            if (profileLink) {
                 profileLink.click();
            } else {
                 window.switchContent('HouseholdProfiles', document.getElementById('mainTitle'));
            }

            const profileForm = document.getElementById('householdProfileForm');
            if (profileForm) {
                profileForm.scrollIntoView(); 
            }

            const household = HOUSEHOLDS_DATA.find(h => h.id === householdId);
            if (!household) return alertMessage('Household not found for editing.', 'error');

            // Set editing mode - this variable is used by saveProfile
            window.editingHouseholdId = household.id;
            
            // Pre-fill Head fields
            document.getElementById('headSurname').value = household.head_surname || '';
            document.getElementById('headFirstname').value = household.head_firstname || '';
            document.getElementById('headMiddleInitial').value = household.head_middle_name || '';
            document.getElementById('headAge').value = household.head_age || '';
            document.getElementById('headGender').value = household.head_gender || '';
            document.getElementById('contact').value = household.contact_number || '';
            document.getElementById('purok').value = household.purok || '';
            document.getElementById('headCondition').value = household.post_disaster_condition || '';
            document.getElementById('headLivelihood').value = household.livelihood_status || '';
            document.getElementById('damageStatus').value = household.damage_status || '';
            document.getElementById('initialNeeds').value = household.initial_needs || '';

            // Clear and pre-fill members
            document.querySelectorAll('.member-row').forEach(row => row.remove());
            
            const members = household.familyMembers || [];
            members.forEach(member => {
                window.addMember();
                const rows = document.querySelectorAll('.member-row');
                const lastRow = rows[rows.length - 1];
                const inputs = lastRow.querySelectorAll('input, select');
                
                if (inputs.length >= 9) {
                    inputs[0].value = member.surname || '';
                    inputs[1].value = member.first_name || '';
                    inputs[2].value = member.middle_initial || '';
                    inputs[3].value = member.age || '';
                    inputs[4].value = member.gender || '';
                    inputs[5].value = member.relationship || '';
                    inputs[6].value = member.livelihood_status || '';
                    inputs[7].value = member.condition_status || '';
                    inputs[8].value = member.residence_status || '';
                }
            });
            
            alertMessage(`Editing Household ID: ${householdId} (${household.headName}). Make your changes and click Save.`, 'info');
        };

        // Function to cancel editing and clear form
        window.cancelEditProfile = () => {
            window.editingHouseholdId = null;
            document.getElementById('householdProfileForm').reset();
            document.querySelectorAll('.member-row').forEach(row => row.remove());
        };
        
        // --- HOUSEHOLD MASTERLIST FILTER AND SEARCH LOGIC ---
		
        window.filterAndSearchHouseholdList = () => {
            const searchText = (document.getElementById('masterlistSearch')?.value || '').toLowerCase();
            const purokFilter = document.getElementById('masterlistPurokFilter')?.value || '';
            const damageFilter = document.getElementById('masterlistDamageFilter')?.value || '';

            const filteredHouseholds = HOUSEHOLDS_DATA.filter(household => {
                const matchesSearch = !searchText || 
                    (household.head_surname && household.head_surname.toLowerCase().includes(searchText));
                const matchesPurok = !purokFilter || household.purok === purokFilter;
                const matchesDamage = !damageFilter || household.damageStatus == damageFilter;

                return matchesSearch && matchesPurok && matchesDamage;
            });

            renderHouseholdList(filteredHouseholds);
        };

        // View high priority households (Redirect to Master List)
        window.viewHighPriorityHouseholds = () => {
            // Switch to Household Profiles section
            const profileLink = document.querySelector('[data-target="HouseholdProfiles"]');
            if (profileLink) {
                profileLink.click();
            } else {
                window.switchContent('HouseholdProfiles', document.getElementById('mainTitle'));
            }

            // Reset filters to show the Master List
            const damageFilter = document.getElementById('masterlistDamageFilter');
            const searchInput = document.getElementById('masterlistSearch');
            const purokFilter = document.getElementById('masterlistPurokFilter');

            if (searchInput) searchInput.value = '';
            if (purokFilter) purokFilter.value = '';
            if (damageFilter) damageFilter.value = '';
            
            // Re-render list with cleared filters
            filterAndSearchHouseholdList();
            
            // Scroll to the Master List section
            setTimeout(() => {
                const masterListHeader = document.querySelector('h2.text-2xl.font-semibold.mt-10.mb-4');
                if(masterListHeader) {
                    masterListHeader.scrollIntoView({ behavior: 'smooth' });
                }
            }, 100);
        };

        // --- HOUSEHOLD MASTERLIST RENDER ---
		
        window.renderHouseholdList = (data) => {
            const tableBody = document.getElementById('householdTableBody');
            if (!tableBody) return;

            if (data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400">No matching household profiles found. Adjust your filters or search term.</td></tr>`;
                return;
            }

            const rowsHTML = data.map((household, index) => {
                const damageStatusMap = {
                    '0': '0% - Unaffected/No Damage', '25': '25% - Minor Damage (Superficial)',
                    '50': '50% - Moderate Damage (Half Loss)', '75': '75% - Major Damage (Structural Loss)',
                    '100': '100% - Total Loss/Destroyed'
                };
                const damageLabel = damageStatusMap[household.damageStatus] || household.damageStatus || 'N/A';

                return `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-semibold text-gray-700">${index + 1}</td>
                        <td class="p-4 text-sm">${household.headName || 'N/A'}</td>
                        <td class="p-4 text-sm">${household.purok || 'N/A'}</td>
                        <td class="p-4 text-sm">${damageLabel}</td>
                        <td class="p-4 text-sm">${household.familyMembers.length || 1}</td>
                        <td class="p-4 text-sm space-x-2 whitespace-nowrap">
                            <button class="text-primary-light hover:text-primary-dark transition" onclick="openDetailsDrawer('${household.id}')">View</button>
                            <button class="text-orange-600 hover:text-orange-800 transition font-medium" onclick="startEditProfile('${household.id}')">Edit</button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            tableBody.innerHTML = rowsHTML;
        };

        // --- AID DISTRIBUTION LIST FUNCTIONS ---

        window.closeAidDetailsDrawer = () => {
            document.getElementById('aidDetailsDrawer').classList.remove('drawer-open');
        };

        window.openAidDetailsDrawer = (aidId) => {
            const aidRecord = AID_RECORDS_DATA.find(r => r.id == aidId);
            if (!aidRecord) return alert('Aid Record not found for ID: ' + aidId);

            const household = HOUSEHOLDS_DATA.find(h => h.id === aidRecord.aidRecipientId);
            const headName = household ? household.headName : 'Household Not Found';
            const purok = household ? household.purok : 'N/A';

            setText('aidDrawerRecipient', headName);
            setText('aidDrawerId', 'Aid #' + aidRecord.id);
            setText('aidDrawerHouseholdId', aidRecord.aidRecipientId);
            setText('aidDrawerPurok', purok);
            setText('aidDrawerType', aidRecord.aidType);
            setText('aidDrawerQuantity', aidRecord.quantity || '1');
            setText('aidDrawerDate', aidRecord.dateDistributed);
            setText('aidDrawerBy', aidRecord.distributedBy || 'N/A');
            setText('aidDrawerNotes', aidRecord.distributionNotes || 'None specified.');

            document.getElementById('aidDrawerEditButton').setAttribute('onclick', `startEditAidRecord('${aidRecord.id}')`);
            document.getElementById('aidDrawerDeleteButton').setAttribute('onclick', `confirmDeleteAidRecord('${aidRecord.id}')`);
            
            document.getElementById('aidDetailsDrawer').classList.add('drawer-open');
            // Ensure Household Drawer is closed if open
            closeDetailsDrawer();
            lucide.createIcons();
        };

        window.startEditAidRecord = (aidId) => {
            closeAidDetailsDrawer();
            
            const aidLink = document.querySelector('[data-target="AidDistributionRecords"]');
            if (aidLink) {
                 aidLink.click();
            } else {
                 window.switchContent('AidDistributionRecords', document.getElementById('mainTitle'));
            }

            const aidForm = document.getElementById('aidDistributionForm');
            if (aidForm) {
                aidForm.scrollIntoView(); 
            }

            const aidRecord = AID_RECORDS_DATA.find(r => r.id == aidId);
            if (!aidRecord) return alertMessage('Aid record not found for editing.', 'error');

            // Set editing mode
            window.editingAidRecordId = aidRecord.id;

            // Pre-fill form (only single select is supported for edit pre-fill)
            document.getElementById('multiSelectToggle').checked = false;
            window.toggleMultiSelect(false);
            document.getElementById('aidRecipientId').value = aidRecord.aidRecipientId;
            document.getElementById('aidType').value = aidRecord.aidType;
            document.getElementById('quantity').value = aidRecord.quantity;
            document.getElementById('dateDistributed').value = aidRecord.dateDistributed;
            document.getElementById('distributedBy').value = aidRecord.distributedBy || '';
            document.getElementById('distributionNotes').value = aidRecord.distributionNotes || '';

            alertMessage(`Editing Aid Record #${aidId}. Make your changes and click Save.`, 'info');
        };

        // Confirm and delete aid record
        window.confirmDeleteAidRecord = (aidId) => {
            const aidRecord = AID_RECORDS_DATA.find(r => r.id == aidId);
            if (!aidRecord) return alertMessage('Aid record not found.', 'error');
            
            const household = HOUSEHOLDS_DATA.find(h => h.id === aidRecord.aidRecipientId);
            const headName = household ? household.headName : 'Unknown';
            
            showModal(`
                <h2 class="text-2xl font-bold text-mgm-dark mb-2">Delete Aid Record?</h2>
                <p class="text-sm text-gray-500 mb-4">This action cannot be undone.</p>
                <div class="p-4 bg-gray-50 rounded-lg border-l-4 border-mgm-dark mb-4">
                    <p class="text-mgm-dark">You are about to permanently delete the aid distribution record for <strong>${headName}</strong> (Aid Type: ${aidRecord.aidType}, Date: ${aidRecord.dateDistributed}).</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="hideModal()" class="flex-1 bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button onclick="deleteAidRecord('${aidId}')" class="flex-1 bg-mgm-dark text-white font-semibold py-2 rounded-lg hover:bg-mgm-deep transition">
                        Delete
                    </button>
                </div>
            `, 'max-w-md');
        };

        window.deleteAidRecord = async (aidId) => {
            hideModal();
            
            try {
                const response = await fetch('api_aid_records.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: aidId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeAidDetailsDrawer();
                    fetchAidRecords(); // Refresh the list
                    updateDashboardMetrics(); // Update dashboard stats
                    // Delay showing popup to let previous modal close properly
                    setTimeout(() => {
                        alertMessage('Aid distribution record deleted successfully!', 'info');
                    }, 350);
                } else {
                    throw new Error(result.error || 'Unknown error');
                }
            } catch (error) {
                setTimeout(() => {
                    alertMessage('Error deleting aid record: ' + error.message, 'error');
                }, 350);
            }
        };
        
        window.filterAndSearchAidList = () => {
            const searchText = (document.getElementById('aidlistSearch')?.value || '').toLowerCase();
            const aidTypeFilter = document.getElementById('aidlistAidTypeFilter')?.value || '';
            const recipientIdFilter = document.getElementById('aidlistRecipientFilter')?.value || '';

            const filteredAidRecords = AID_RECORDS_DATA.filter(record => {
                // Extract surname from recipientName (format: "Surname, Firstname")
                const recipientName = record.recipientName || '';
                const surname = recipientName.split(',')[0].toLowerCase().trim();

                // Search Filter - surname only
                const matchesSearch = !searchText || surname.includes(searchText);

                // Aid Type Filter
                const matchesAidType = !aidTypeFilter || record.aidType === aidTypeFilter;

                // Recipient/Household Filter
                const matchesRecipient = !recipientIdFilter || record.aidRecipientId === recipientIdFilter;

                return matchesSearch && matchesAidType && matchesRecipient;
            });

            renderAidRecords(filteredAidRecords);
        };
        
        window.renderAidRecords = (data) => {
            const tableBody = document.getElementById('aidRecordTableBody');
            if (!tableBody) return;
           
            tableBody.innerHTML = '';

            if (data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400">No matching aid distribution records found.</td></tr>`;
                return;
            }


            const rowsHTML = data.map((record, index) => {
                // Use recipientName from API directly
                const headName = record.recipientName || 'Unknown';
                const date = record.dateDistributed || 'N/A';

                return `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-semibold text-gray-700">${index + 1}</td>
                        <td class="p-4 text-sm">${headName}</td>
                        <td class="p-4 text-sm">${record.aidType || 'N/A'}</td>
                        <td class="p-4 text-sm">${record.quantity || '1'}</td>
                        <td class="p-4 text-sm">${date}</td>
                        <td class="p-4 text-sm space-x-2 whitespace-nowrap">
                            <button class="text-aid-color hover:text-aid-color-dark transition" onclick="openAidDetailsDrawer('${record.id}')">View</button>
                            <button class="text-red-600 hover:text-red-800 transition font-medium" onclick="startEditAidRecord('${record.id}')">Edit</button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            tableBody.innerHTML = rowsHTML;
        };

        // --- GENERAL/UTILITY FUNCTIONS ---

        const populateHouseholdDropdown = () => {
            const singleSelect = document.getElementById('aidRecipientId');
            const multiSelectDiv = document.getElementById('multiSelectCheckboxes');
            const aidRecipientFilter = document.getElementById('aidlistRecipientFilter');

            if (!singleSelect || !multiSelectDiv || !aidRecipientFilter) return;

            const householdsToUse = HOUSEHOLDS_DATA;

            // Clear and populate single select & filter select
            singleSelect.innerHTML = '<option value="">Select Single Household Head</option>';
            aidRecipientFilter.innerHTML = '<option value="">All Recipients</option>';
           
            multiSelectDiv.innerHTML = '';
           
            if (householdsToUse.length === 0) {
                multiSelectDiv.innerHTML = '<p class="text-xs text-gray-400 p-2">No households profiled yet.</p>';
                return;
            }

            householdsToUse.forEach(h => {
                const idShort = h.id.substring(0, 8);
                const name = h.headName || 'Unnamed Head';
                const display = `ID: ${idShort} - ${name}`;
               
                const option = document.createElement('option');
                option.value = h.id;
                option.textContent = display;
                singleSelect.appendChild(option.cloneNode(true));
                aidRecipientFilter.appendChild(option.cloneNode(true));
               
                const checkboxWrapper = document.createElement('div');
                checkboxWrapper.className = 'flex items-center space-x-2 py-1 border-b border-gray-100 last:border-b-0';
                checkboxWrapper.innerHTML = `
                    <input type="checkbox" id="recipient-${h.id}" name="recipient-${h.id}" value="${h.id}" class="h-4 w-4 text-primary-dark border-gray-300 rounded-md focus:ring-primary-light recipient-checkbox">
                    <label for="recipient-${h.id}" class="text-sm text-gray-700 cursor-pointer">${display}</label>
                `;
                multiSelectDiv.appendChild(checkboxWrapper);
            });
        };

        window.toggleSidebar = () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
           
            if (isSidebarOpen) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                overlay.classList.remove('opacity-100', 'pointer-events-auto');
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
            }
            isSidebarOpen = !isSidebarOpen;
        };
       
        window.switchContent = (targetId, clickedElement) => {
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.add('hidden');
            });
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.remove('hidden');
               
                const headerTitle = document.getElementById('mainTitle');
                if (headerTitle) {
                     headerTitle.textContent = clickedElement.textContent.trim();
                }
            }

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            clickedElement.classList.add('active');
           
            // Refresh dashboard data when switching to Dashboard
            if (targetId === 'Dashboard') {
                updateDashboardMetrics();
            }
           
            if (window.innerWidth < 1024 && isSidebarOpen) {
                window.toggleSidebar();
            }
        };

        const MEMBER_ROW_HTML = (iconHtml) => `
            <div class="col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Surname</label>
                    <input type="text" id="memberSurname" placeholder="Surname (e.g., Bacsarsa)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">First Name</label>
                    <input type="text" id="memberFirstName" placeholder="First Name (e.g., Vin)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Middle Initial</label>
                    <input type="text" id="memberMiddleInitial" placeholder="Middle Initial (e.g., M)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Age</label>
                    <input type="number" placeholder="e.g., 22" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Gender</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Relationship</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select Relationship</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Child">Child</option>
                        <option value="Parent">Parent/In-Law</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Livelihood Status</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select Status</option>
                        <option value="Employed">Employed</option>
                        <option value="Self-Employed">Self-Employed</option>
                        <option value="Unemployed">Unemployed</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Post-Disaster Condition</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select Status</option>
                        <option value="Alive">Normal/Unaffected</option>
                        <option value="Injured">Injured</option>
                        <option value="Missing">Missing</option>
                        <option value="Deceased">Deceased</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Residence Status</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select Status</option>
                        <option value="Resident">Permanent Resident</option>
                        <option value="Transferred">Transferred to Evacuation Center</option>
                        <option value="Outside">Temporarily Outside Barangay</option>
                    </select>
                </div>
            </div>

            <div class="col-span-12 flex justify-end pt-2">
                <button type="button" onclick="removeMember(this)" class="text-sm font-semibold text-red-500 hover:text-red-700 flex items-center transition">
                    <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Remove Member
                </button>
            </div>
        `;

        window.addMember = () => {
            const container = document.getElementById('familyMembersContainer');
            const newRow = document.createElement('div');
            newRow.className = 'grid grid-cols-1 md:grid-cols-12 gap-4 border-t border-gray-100 pt-3 mt-3 member-row';
            newRow.innerHTML = MEMBER_ROW_HTML(); // Use the template function
            container.appendChild(newRow);
            lucide.createIcons();
        };

        window.removeMember = (button) => {
            const row = button.closest('.member-row');
            if (row) {
                row.remove();
            }
        };

        window.saveProfile = async (event) => {
            event.preventDefault();
            const messageElement = document.getElementById('saveMessage');
            
            // Collect Head Data
            const headData = {
                headSurname: document.getElementById('headSurname').value,
                headFirstname: document.getElementById('headFirstname').value,
                headMiddleInitial: document.getElementById('headMiddleInitial').value,
                headAge: document.getElementById('headAge').value,
                headGender: document.getElementById('headGender').value,
                contactNumber: document.getElementById('contact').value,
                purok: document.getElementById('purok').value,
                headCondition: document.getElementById('headCondition').value,
                headLivelihood: document.getElementById('headLivelihood').value,
                damageStatus: document.getElementById('damageStatus').value,
                initialNeeds: document.getElementById('initialNeeds').value,
                members: []
            };

            // Collect Members Data
            document.querySelectorAll('.member-row').forEach(row => {
                // Ensure inputs in addMember template have these classes or are selected by structure
                // Currently they have IDs which is bad for multiples, so we should select by index or position
                const inputs = row.querySelectorAll('input, select');
                // Assuming order: Surname, Firstname, Middle, Age, Gender, Relationship, Livelihood, Condition, Residence
                // This relies on the exact DOM structure. A better way is to update MEMBER_ROW_HTML to use classes.
                // For now, I'll update MEMBER_ROW_HTML in a separate step or try to select robustly.
                // Let's assume I will update classes in MEMBER_ROW_HTML first.
                // Wait, I can't update MEMBER_ROW_HTML in the same step easily.
                // I will try to select by name attribute if I added them? I haven't added name attributes to members yet.
                // I'll select by order in the grid.
                
                headData.members.push({
                    surname: inputs[0].value,
                    firstName: inputs[1].value,
                    middleInitial: inputs[2].value,
                    age: inputs[3].value,
                    gender: inputs[4].value,
                    relationship: inputs[5].value,
                    livelihoodStatus: inputs[6].value,
                    conditionStatus: inputs[7].value,
                    residenceStatus: inputs[8].value
                });
            });

            try {
                // Determine if we're in edit mode
                const isEditing = !!window.editingHouseholdId;
                const method = isEditing ? 'PUT' : 'POST';
                
                // Add ID if editing
                if (isEditing) {
                    headData.id = window.editingHouseholdId;
                }
                
                const response = await fetch('api_households.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(headData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const message = isEditing 
                        ? 'Household profile updated successfully!' 
                        : 'Household profile saved successfully!';
                    alertMessage(message, 'info');
                    
                    // Clear editing state
                    window.editingHouseholdId = null;
                    
                    event.target.reset();
                    document.querySelectorAll('.member-row').forEach(row => row.remove());
                    fetchHouseholds(); // Refresh the list
                    
                    // Log Action
                    const logAction = isEditing ? 'Updated Household Profile' : 'Added Household Profile';
                    const targetName = `${headData.headFirstname} ${headData.headSurname}`;
                    const target = isEditing ? `Household: ${targetName} (ID: ${headData.id})` : `Household: ${targetName}`;
                    logSystemAction(logAction, target);
                } else {
                    throw new Error(result.error || 'Unknown error');
                }
            } catch (error) {
                alertMessage('Error saving profile: ' + error.message, 'error');
            }
        };
       
        window.toggleMultiSelect = (isMulti) => {
            const singleContainer = document.getElementById('singleRecipientContainer');
            const multiContainer = document.getElementById('multiRecipientContainer');
            const singleSelect = document.getElementById('aidRecipientId');
           
            if (isMulti) {
                singleContainer.classList.add('hidden');
                multiContainer.classList.remove('hidden');
                singleSelect.removeAttribute('required');
            } else {
                singleContainer.classList.remove('hidden');
                multiContainer.classList.add('hidden');
                singleSelect.setAttribute('required', 'required');
            }

            document.getElementById('aidRecipientId').value = '';
            document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
        };
       
        window.selectAllRecipients = () => {
            const checkboxes = document.querySelectorAll('#multiSelectCheckboxes .recipient-checkbox');
            if (checkboxes.length === 0) return;
           
            const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
            const shouldSelectAll = checkedCount === 0;

            checkboxes.forEach(checkbox => {
                checkbox.checked = shouldSelectAll;
            });
        };

        window.saveAidRecord = async (event) => {
            event.preventDefault();
            const form = event.target;
            const isMultiSelect = document.getElementById('multiSelectToggle').checked;
            let selectedIds = [];

            if (isMultiSelect) {
                document.querySelectorAll('.recipient-checkbox:checked').forEach(checkbox => {
                    selectedIds.push(checkbox.value);
                });
            } else {
                const singleId = document.getElementById('aidRecipientId').value;
                if (singleId) {
                    selectedIds.push(singleId);
                }
            }

            const messageElement = document.getElementById('aidSaveMessage');
           
            if (selectedIds.length === 0) {
                 messageElement.textContent = 'Please select at least one household recipient.';
                 messageElement.className = 'p-3 rounded-md text-sm mb-4 bg-yellow-100 text-yellow-800';
                 messageElement.classList.remove('hidden');
                 setTimeout(() => { messageElement.classList.add('hidden'); }, 5000);
                 return;
            }

            // Collect form data
            const aidData = {
                householdId: selectedIds.length === 1 ? selectedIds[0] : selectedIds,
                aidType: document.getElementById('aidType').value,
                quantity: document.getElementById('quantity').value,
                dateDistributed: document.getElementById('dateDistributed').value,
                distributedBy: document.getElementById('distributedBy').value,
                notes: document.getElementById('distributionNotes').value
            };

            try {
                // Determine if we're in edit mode
                const isEditing = !!window.editingAidRecordId;
                const method = isEditing ? 'PUT' : 'POST';
                
                // Add ID if editing
                if (isEditing) {
                    aidData.id = window.editingAidRecordId;
                }
                
                const response = await fetch('api_aid_records.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(aidData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const message = isEditing 
                        ? 'Aid distribution record updated successfully!'
                        : `Successfully saved ${result.insertedCount || selectedIds.length} aid distribution record(s)!`;
                    
                    // Clear editing state
                    window.editingAidRecordId = null;
                    
                    // Reset form
                    if (isMultiSelect) {
                        document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        document.getElementById('aidRecipientId').value = '';
                    }
                    form.querySelector('#aidType').value = '';
                    form.querySelector('#quantity').value = '';
                    form.querySelector('#dateDistributed').value = '';
                    form.querySelector('#distributedBy').value = '';
                    form.querySelector('#distributionNotes').value = '';
                    
                    fetchAidRecords(); // Refresh the list from database
                    updateDashboardMetrics(); // Update dashboard stats
                    
                    // Log Action
                    const logActionText = isEditing ? 'Updated Aid Record' : 'Recorded Aid Distribution';
                    const targetText = isEditing ? `Record ID: ${aidData.id}` : `Records: ${result.insertedCount || selectedIds.length}`;
                    logSystemAction(logActionText, targetText);
                    
                    // Show success popup after a delay
                    setTimeout(() => {
                        alertMessage(message, 'info');
                    }, 100);
                } else {
                    throw new Error(result.error || 'Unknown error');
                }
            } catch (error) {
                alertMessage('Error saving record: ' + error.message, 'error');
            }
        };

        window.generateReport = (format) => {
            const filterPurok = document.getElementById('reportPurokFilter').value;
            const filterDamage = document.getElementById('reportDamageFilter').value;
            
            // Filter households based on selected criteria - using snake_case field names from API
            const filteredData = HOUSEHOLDS_DATA.filter(h => {
                const matchesPurok = !filterPurok || h.purok === filterPurok;
                const matchesDamage = !filterDamage || String(h.damage_status) === filterDamage;
                return matchesPurok && matchesDamage;
            });

            const resultElement = document.getElementById('reportOutput');
            
            if (filteredData.length === 0) {
                resultElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                resultElement.classList.add('bg-red-100', 'text-red-800');
                resultElement.textContent = 'No households match the selected filter criteria.';
                setTimeout(() => { resultElement.classList.add('hidden'); }, 5000);
                return;
            }

            // Generate report based on format
            try {
                if (format === 'PDF') {
                    generatePDFReport(filteredData, filterPurok, filterDamage);
                } else if (format === 'Excel') {
                    generateExcelReport(filteredData, filterPurok, filterDamage);
                } else if (format === 'CSV') {
                    generateCSVReport(filteredData, filterPurok, filterDamage);
                }
                
                resultElement.classList.remove('hidden', 'bg-red-100', 'text-red-800');
                resultElement.classList.add('bg-green-100', 'text-green-800');
                resultElement.textContent = `Successfully generated ${format} report for ${filteredData.length} households.`;
                setTimeout(() => { resultElement.classList.add('hidden'); }, 7000);
            } catch (error) {
                resultElement.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                resultElement.classList.add('bg-red-100', 'text-red-800');
                resultElement.textContent = `Error generating ${format} report: ${error.message}`;
                setTimeout(() => { resultElement.classList.add('hidden'); }, 7000);
            }
        };

        function generatePDFReport(data, filterPurok, filterDamage) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Title
            doc.setFontSize(18);
            doc.setTextColor(0, 77, 64);
            doc.text('PDRIMS Household Report', 14, 20);
            
            // Subtitle with filter info
            doc.setFontSize(10);
            doc.setTextColor(100);
            const filterInfo = [];
            if (filterPurok) filterInfo.push(`Purok: ${filterPurok}`);
            if (filterDamage) filterInfo.push(`Damage: ${filterDamage}%`);
            doc.text(filterInfo.length > 0 ? filterInfo.join(' | ') : 'All Households', 14, 28);
            doc.text(`Generated: ${new Date().toLocaleString()} | Total: ${data.length} households`, 14, 34);

            let currentY = 42;

            // --- DASHBOARD SUMMARY SECTION ---
            if (window.LATEST_DASHBOARD_DATA) {
                const dash = window.LATEST_DASHBOARD_DATA;
                
                // 1. Key Metrics Row
                doc.setFontSize(12);
                doc.setTextColor(0, 77, 64);
                doc.text("Dashboard Summary", 14, currentY);
                currentY += 5;

                const metricWidth = 40;
                const metricHeight = 20;
                const gap = 5;
                let startX = 14;

                // Helper to draw metric box
                const drawMetric = (label, value, colorHex, x) => {
                    doc.setFillColor(250, 250, 250);
                    doc.setDrawColor(200, 200, 200);
                    doc.rect(x, currentY, metricWidth, metricHeight, 'FD');
                    
                    doc.setFontSize(8);
                    doc.setTextColor(100);
                    doc.text(label, x + 2, currentY + 6);
                    
                    doc.setFontSize(14);
                    doc.setTextColor(colorHex);
                    doc.setFont(undefined, 'bold');
                    doc.text(String(value), x + 2, currentY + 16);
                    doc.setFont(undefined, 'normal');
                };

                drawMetric("Profiled", dash.totalProfiled || 0, "#DC2626", startX);
                drawMetric("Destroyed", dash.totalDestroyed || 0, "#D97706", startX + metricWidth + gap);
                drawMetric("Priority", dash.totalPriority || 0, "#EA580C", startX + (metricWidth + gap) * 2);
                drawMetric("Recovery", (dash.recoveryPercent || 0) + "%", "#16A34A", startX + (metricWidth + gap) * 3);

                currentY += metricHeight + 10;

                // 2. Charts Row
                const chartHeight = 55; // Increased height for Damage List
                
                // --- Damage Status Distribution (Left) ---
                doc.setFontSize(10);
                doc.setTextColor(0);
                doc.text("Damage Status Distribution", 14, currentY);
                
                const damageStats = dash.damageStats || {};
                const totalDamage = Object.values(damageStats).reduce((a, b) => a + b, 0) || 1;
                const damageKeys = ['100', '75', '50', '25', '0'];
                const damageLabels = {
                    '100': 'Total Loss (100%)', '75': 'Major Damage (75%)', 
                    '50': 'Moderate (50%)', '25': 'Minor (25%)', '0': 'No Damage (0%)'
                };
                const damageColors = {
                    '100': '#DC2626', '75': '#EA580C', '50': '#EAB308', '25': '#3B82F6', '0': '#22C55E'
                };

                let barY = currentY + 6;
                const barMaxWidth = 80;
                
                damageKeys.forEach(key => {
                    const count = damageStats[key] || 0;
                    const pct = Math.round((count / totalDamage) * 100);
                    
                    // Label and Count
                    doc.setFontSize(7);
                    doc.setTextColor(50);
                    doc.text(damageLabels[key], 14, barY);
                    doc.text(`${count} (${pct}%)`, 14 + barMaxWidth, barY, { align: 'right' });
                    
                    // Background Bar
                    barY += 1.5;
                    doc.setFillColor(229, 231, 235); // gray-200
                    doc.rect(14, barY, barMaxWidth, 2.5, 'F');
                    
                    // Foreground Bar
                    if (pct > 0) {
                        doc.setFillColor(damageColors[key]);
                        doc.rect(14, barY, (pct / 100) * barMaxWidth, 2.5, 'F');
                    }
                    
                    barY += 7; // Gap for next item
                });

                // --- Aid Fulfillment (Right) - Circular Chart ---
                const aidRecov = parseFloat(dash.recoveryPercent || 0);
                const aidX = 140; // Center X for circle
                const aidY = currentY + 20; // Center Y for circle
                const radius = 12;

                doc.setFontSize(10);
                doc.setTextColor(0);
                doc.text("Aid Fulfillment Rate", 110, currentY);

                // Helper to draw ring (Gray background)
                doc.setDrawColor(229, 231, 235); // gray-200
                doc.setLineWidth(4);
                doc.circle(aidX, aidY, radius, 'S');

                // Draw Progress Arc (Green foreground)
                if (aidRecov > 0) {
                    doc.setDrawColor(22, 163, 74); // green-600
                   
                    const startAngle = -Math.PI / 2; // -90 deg (top)
                    const endAngle = startAngle + (aidRecov / 100) * (2 * Math.PI);
                    
                    // Draw arc using small segments
                    const step = 0.1;
                    let x1 = aidX + radius * Math.cos(startAngle);
                    let y1 = aidY + radius * Math.sin(startAngle);
                    
                    for (let theta = startAngle; theta <= endAngle; theta += step) {
                        let x2 = aidX + radius * Math.cos(theta);
                        let y2 = aidY + radius * Math.sin(theta);
                        doc.line(x1, y1, x2, y2);
                        x1 = x2; y1 = y2;
                    }
                    // Connect to exact end
                    let xFinal = aidX + radius * Math.cos(endAngle);
                    let yFinal = aidY + radius * Math.sin(endAngle);
                    doc.line(x1, y1, xFinal, yFinal);
                }

                // Text in center
                doc.setFontSize(8);
                doc.setTextColor(22, 163, 74); // green-600
                doc.setFont(undefined, 'bold');
                const textW = doc.getTextWidth(`${aidRecov}%`);
                doc.text(`${aidRecov}%`, aidX - (textW / 2), aidY + 1);
                
                // Label below
                doc.setFontSize(8);
                doc.setTextColor(100);
                doc.setFont(undefined, 'normal');
                doc.text("Needs Met", aidX - 6, aidY + radius + 8);

                currentY += chartHeight + 15;

                // 3. AI Suggestions Section
                doc.setFontSize(10);
                doc.setTextColor(75, 0, 130); // Indigo
                doc.setFont(undefined, 'bold');
                doc.text("AI Suggestions & Recommendations", 14, currentY);
                doc.setFont(undefined, 'normal');
                currentY += 6;

                const pdfSuggestions = [];
                const totalProfiled = dash.totalProfiled || 0;
                const totalPriority = dash.totalPriority || 0;
                const recoveryPercent = dash.recoveryPercent || 0;
                const pdfDamageStats = dash.damageStats || {};

                if (totalPriority > 0) {
                    const priorityPct = Math.round((totalPriority / totalProfiled) * 100);
                    pdfSuggestions.push(`[CRITICAL] ${totalPriority} household(s) (${priorityPct}%) have >=75% damage. Prioritize immediate relief.`);
                }
                if (recoveryPercent < 25) {
                    pdfSuggestions.push(`[WARNING] Aid fulfillment at ${recoveryPercent}%. Accelerate relief operations.`);
                } else if (recoveryPercent >= 75) {
                    pdfSuggestions.push(`[SUCCESS] Aid fulfillment at ${recoveryPercent}%. Focus on remaining households.`);
                }
                if ((pdfDamageStats['100'] || 0) > 0) {
                    pdfSuggestions.push(`[INFO] ${pdfDamageStats['100']} household(s) with total loss. Coordinate housing reconstruction.`);
                }
                if (totalProfiled === 0) {
                    pdfSuggestions.push(`[INFO] No households profiled. Begin damage assessments.`);
                } else if (totalProfiled > 0 && recoveryPercent === 0) {
                    pdfSuggestions.push(`[WARNING] ${totalProfiled} households profiled but no aid distributed. Begin relief.`);
                }

                if (pdfSuggestions.length === 0) {
                    pdfSuggestions.push('No specific recommendations. Data looks balanced.');
                }

                doc.setFontSize(8);
                doc.setTextColor(60);
                pdfSuggestions.forEach(s => {
                    const lines = doc.splitTextToSize(`• ${s}`, 180);
                    lines.forEach(line => {
                        doc.text(line, 14, currentY);
                        currentY += 4;
                    });
                });
                currentY += 5;
            }
            
            // Prepare table data - using snake_case field names from API
            const tableData = data.map((h, i) => [
                i + 1,
                `${h.head_firstname || ''} ${h.head_surname || ''}`.trim() || h.full_name || 'N/A',
                h.purok || 'N/A',
                h.contact_number || 'N/A',
                `${h.damage_status || 0}%`,
                (h.members?.length || 0) + 1
            ]);

            doc.autoTable({
                startY: currentY,
                head: [['#', 'Head of Household', 'Purok', 'Contact', 'Damage', 'Members']],
                body: tableData,
                theme: 'striped',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [0, 77, 64], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 245, 245] }
            });

            // Footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                doc.text('PDRIMS - Post-Disaster Recovery Information Management System', 14, doc.internal.pageSize.height - 10);
            }

            doc.save(`PDRIMS_Household_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function generateExcelReport(data, filterPurok, filterDamage) {
            // Prepare worksheet data
            const wsData = [
                ['PDRIMS Household Report'],
                [`Generated: ${new Date().toLocaleString()}`],
                [`Filter: ${filterPurok || 'All Puroks'} | ${filterDamage ? filterDamage + '% Damage' : 'All Damage Levels'}`],
                [],
                ['#', 'Head Surname', 'Head First Name', 'Purok', 'Contact', 'Damage %', 'Condition', 'Livelihood', 'Initial Needs', 'Members']
            ];

            // Using snake_case field names from API
            data.forEach((h, i) => {
                wsData.push([
                    i + 1,
                    h.head_surname || '',
                    h.head_firstname || '',
                    h.purok || '',
                    h.contact_number || '',
                    h.damage_status || 0,
                    h.post_disaster_condition || '',
                    h.livelihood_status || '',
                    h.initial_needs || '',
                    (h.members?.length || 0) + 1
                ]);
            });

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Set column widths
            ws['!cols'] = [
                { wch: 5 }, { wch: 15 }, { wch: 15 }, { wch: 12 }, { wch: 15 },
                { wch: 10 }, { wch: 12 }, { wch: 15 }, { wch: 25 }, { wch: 10 }
            ];

            XLSX.utils.book_append_sheet(wb, ws, 'Households');
            XLSX.writeFile(wb, `PDRIMS_Household_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        function generateCSVReport(data, filterPurok, filterDamage) {
            const headers = ['#', 'Head Surname', 'Head First Name', 'Middle Initial', 'Age', 'Gender', 'Purok', 'Contact', 'Damage %', 'Condition', 'Livelihood', 'Initial Needs', 'Member Count'];
            
            // Using snake_case field names from API
            const rows = data.map((h, i) => [
                i + 1,
                `"${h.head_surname || ''}"`,
                `"${h.head_firstname || ''}"`,
                `"${h.head_middle_name || ''}"`,
                h.head_age || '',
                `"${h.head_gender || ''}"`,
                `"${h.purok || ''}"`,
                `"${h.contact_number || ''}"`,
                h.damage_status || 0,
                `"${h.post_disaster_condition || ''}"`,
                `"${h.livelihood_status || ''}"`,
                `"${h.initial_needs || ''}"`,
                (h.members?.length || 0) + 1
            ]);

            const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `PDRIMS_Household_Report_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const defaultNav = document.querySelector('[data-target="Dashboard"]');
            if (defaultNav) {
                window.switchContent('Dashboard', defaultNav);
            }
            window.addMember();

            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                const targetId = link.getAttribute('data-target');
                if (targetId) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        window.switchContent(targetId, link);
                    });
                }
            });

            document.getElementById('dateDistributed').value = new Date().toISOString().split('T')[0];

            updateDashboardMetrics();
            filterAndSearchHouseholdList();
            populateHouseholdDropdown(); 
            filterAndSearchAidList();

        });

    </script>
</head>
<body class="bg-gray-100 p-4">

    <button id="menuButton" onclick="toggleSidebar()" class="lg:hidden fixed bottom-4 left-4 z-50 p-4 bg-primary-dark text-white rounded-full shadow-xl transition duration-300 transform hover:scale-105 active:scale-95">
        <i data-lucide="menu" class="w-6 h-6"></i>
    </button>
   
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="lg:hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-30 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <div class="flex w-full h-[calc(100vh-2rem)]">

        <aside id="sidebar" class="w-70 bg-gradient-to-b from-primary-dark to-primary-deep flex-shrink-0 h-full overflow-y-auto flex flex-col shadow-xl
                           
                            fixed top-0 left-0 z-40 transform -translate-x-full transition-transform duration-300 ease-in-out
                           
                            lg:relative lg:translate-x-0 lg:rounded-lg lg:h-full">

            <div class="p-6 pt-8 pb-4 border-b border-white/20">
                <h1 class="text-white text-2xl font-extrabold uppercase tracking-wide">PDRIMS</h1>
                <p class="text-white/70 text-sm mt-1">Barangay Recovery System</p>
            </div>

            <nav class="flex-grow mt-4 space-y-1">
                <a href="#" class="nav-link active" data-target="Dashboard">
                    <i data-lucide="home"></i>
                    Dashboard
                </a>
                <a href="#" class="nav-link" data-target="HouseholdProfiles">
                    <i data-lucide="users"></i>
                    Household Profiles
                </a>
                <a href="#" class="nav-link" data-target="AidDistributionRecords">
                    <i data-lucide="hand-heart"></i>
                    Aid Distribution Records
                </a>
                <a href="#" class="nav-link" data-target="ReportsExports">
                    <i data-lucide="file-text"></i>
                    Reports & Exports
                </a>

            </nav>

            <div class="p-6 text-xs text-white/70 border-t border-white/10 flex justify-between items-center group">
                <div>
                    <p id="userIdDisplay">User ID: Loading...</p>
                    <p class="mt-1 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-400 mr-2"></span>
                        <span id="userRoleDisplay">Loading...</span>
                    </p>
                </div>
                <button onclick="confirmLogout()" class="text-white/80 hover:text-red-500 hover:drop-shadow-[0_0_8px_rgba(239,68,68,1)] p-2 transition-all duration-200" title="Logout">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </button>
            </div>
        </aside>

        <div id="detailsDrawer" class="drawer fixed top-4 right-4 w-full md:w-96 h-[calc(100vh-2rem)] bg-white shadow-2xl z-50 overflow-y-auto p-6 flex flex-col rounded-lg rounded-tr-none rounded-br-none">
            <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-primary-dark">Household Details</h2>
                <button onclick="closeDetailsDrawer()" class="text-gray-500 hover:text-gray-800 transition">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div id="drawerContent" class="flex-grow pt-4 space-y-4">
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm font-semibold text-gray-600">Head of Household</p>
                    <p class="text-lg font-bold text-gray-800" id="drawerHeadName">N/A</p>
                    <div class="text-sm mt-2 space-y-1">
                        <p class="text-gray-700"><span class="font-medium">Age:</span> <span id="drawerAge">N/A</span></p>
                        <p class="text-gray-700"><span class="font-medium">Contact:</span> <span id="drawerContact">N/A</span></p>
                    </div>
                    <p class="text-xs text-gray-500 mt-2" id="drawerId">ID: N/A</p>
                </div>

                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="map-pin" class="w-4 h-4 mr-2 text-primary-light"></i> Location & Damage</h3>
                    <p class="text-sm"><span class="font-medium">Purok:</span> <span id="drawerPurok">N/A</span></p>
                    <p class="text-sm"><span class="font-medium">Damage Status:</span> <span id="drawerDamageStatus" class="font-bold text-red-600">N/A</span></p>
                    <p class="text-sm text-gray-500 mt-2">Initial Needs: <span id="drawerNeeds">N/A</span></p>
                </div>
               
                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="users" class="w-4 h-4 mr-2 text-primary-light"></i> Family Members (<span id="drawerMemberCount">0</span>)</h3>
                    <ul id="drawerMemberList" class="list-disc pl-5 text-sm space-y-1">
                        <li class="text-gray-500">No members listed.</li>
                    </ul>
                </div>

                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="hand-heart" class="w-4 h-4 mr-2 text-aid-color"></i> Aid Distribution History (<span id="drawerAidCount">0</span>)</h3>
                    <ul id="drawerAidList" class="text-sm space-y-2">
                        <li class="text-gray-500 p-2">No recorded aid distribution yet.</li>
                    </ul>
                </div>
            </div>

            <div class="border-t pt-4 mt-auto space-y-2">
                <button id="drawerEditButton" onclick="startEditProfile('mock-id')" class="w-full bg-primary-light text-white px-4 py-2 rounded-md font-semibold hover:bg-primary-dark transition flex items-center justify-center shadow-lg">
                    <i data-lucide="edit" class="w-5 h-5 inline-block mr-2"></i> Edit Profile Data
                </button>
                <button id="drawerDeleteButton" onclick="confirmDeleteHousehold('mock-id')" class="w-full bg-mgm-dark text-white px-4 py-2 rounded-md font-semibold hover:bg-mgm-deep transition flex items-center justify-center shadow-md">
                    <i data-lucide="trash-2" class="w-5 h-5 inline-block mr-2"></i> Delete Household
                </button>
            </div>
        </div>
        
        <div id="aidDetailsDrawer" class="drawer fixed top-4 right-4 w-full md:w-96 h-[calc(100vh-2rem)] bg-white shadow-2xl z-50 overflow-y-auto p-6 flex flex-col rounded-lg rounded-tr-none rounded-br-none">
            <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-aid-color">Distribution Record Details</h2>
                <button onclick="closeAidDetailsDrawer()" class="text-gray-500 hover:text-gray-800 transition">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="flex-grow pt-4 space-y-4">
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm font-semibold text-gray-600">Recipient Household</p>
                    <p class="text-lg font-bold text-gray-800" id="aidDrawerRecipient">N/A</p>
                    <p class="text-xs text-gray-500 mt-1" id="aidDrawerHouseholdId">Household ID: N/A</p>
                </div>

                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="tag" class="w-4 h-4 mr-2 text-aid-color"></i> Aid Information</h3>
                    <p class="text-sm"><span class="font-medium">Aid Type:</span> <span id="aidDrawerType" class="font-bold text-primary-dark">N/A</span></p>
                    <p class="text-sm"><span class="font-medium">Quantity/Value:</span> <span id="aidDrawerQuantity" class="text-gray-700">N/A</span></p>
                    <p class="text-sm"><span class="font-medium">Date Distributed:</span> <span id="aidDrawerDate">N/A</span></p>
                </div>
               
                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="building-2" class="w-4 h-4 mr-2 text-aid-color"></i> Source & Location</h3>
                    <p class="text-sm"><span class="font-medium">Distributed By:</span> <span id="aidDrawerBy">N/A</span></p>
                    <p class="text-sm"><span class="font-medium">Purok:</span> <span id="aidDrawerPurok">N/A</span></p>
                    <p class="text-xs text-gray-500 mt-2" id="aidDrawerId">Record ID: N/A</p>
                </div>
                
                <div class="bg-white p-4 rounded-md shadow-md border border-gray-100">
                    <h3 class="font-semibold text-gray-700 border-b pb-2 mb-2 flex items-center"><i data-lucide="notebook-text" class="w-4 h-4 mr-2 text-aid-color"></i> Notes</h3>
                    <p class="text-sm italic text-gray-600" id="aidDrawerNotes">None specified.</p>
                </div>

            </div>

            <div class="border-t pt-4 mt-auto space-y-2">
                <button id="aidDrawerEditButton" onclick="startEditAidRecord('aid-id')" class="w-full bg-aid-color text-white px-4 py-2 rounded-md font-semibold hover:bg-aid-color-dark transition flex items-center justify-center shadow-lg">
                    <i data-lucide="edit" class="w-5 h-5 inline-block mr-2"></i> Edit Distribution Record
                </button>
                <button id="aidDrawerDeleteButton" onclick="confirmDeleteAidRecord('aid-id')" class="w-full bg-mgm-dark text-white px-4 py-2 rounded-md font-semibold hover:bg-mgm-deep transition flex items-center justify-center shadow-md">
                    <i data-lucide="trash-2" class="w-5 h-5 inline-block mr-2"></i> Delete Distribution Record
                </button>
            </div>
        </div>
        <div class="flex-grow w-full lg:ml-4 h-full overflow-hidden">
            <main id="main-content" class="h-full p-8 bg-gradient-to-br from-white to-gray-50 shadow-xl rounded-lg overflow-y-auto">
           
                <header class="mb-4 pb-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-extrabold text-primary-dark" id="mainTitle">Dashboard</h1>
                        <p class="text-gray-500 mt-1">Overview and real-time statistics for post-disaster monitoring.</p>
                    </div>
                    <div class="text-sm font-semibold text-gray-700 bg-red-100 px-4 py-2 rounded-md">
                        Post-Disaster Monitored: Sta. Cruz Fire (2025)
                    </div>
                </header>

                <section id="Dashboard" class="content-section hidden">
                   
                    <div class="bg-gray-50 p-8 rounded-lg border border-gray-200 mb-6 mt-6">
                        <h2 class="text-xl font-bold text-primary-dark mb-6">Key Metrics Overview</h2>
                       
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Affected Households Profiled</p>
                                <p class="text-4xl font-bold mt-2 rotate-1 text-red-600" id="stat-profiled-count">125</p>
                                <p class="text-xs text-gray-400 mt-1">Total unique records</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Totally Destroyed Homes (100% Loss)</p>
                                <p class="text-4xl font-bold mt-2 text-yellow-600" id="stat-destroyed-count">15</p>
                                <p class="text-xs text-gray-400 mt-1">Needs verification</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Average Aid Distribution Rate</p>
                                <p class="text-4xl font-bold mt-2 text-green-600" id="stat-recovery-percent">15%</p>
                                <p class="text-xs text-gray-400 mt-1">Based on total aid records vs households</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">High Priority Households (>= 75% Damage)</p>
                                <p class="text-4xl font-bold mt-2 text-orange-600" id="stat-priority-count">28</p>
                         
                                <div class="absolute bottom-4 right-4">
                                    <button onclick="viewHighPriorityHouseholds()" class="bg-orange-500 text-white px-4 py-2 rounded-md text-sm hover:bg-orange-600 transition shadow-md whitespace-nowrap">
                                        View List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                   
                    <div class="bg-gray-50 p-8 rounded-lg border border-gray-200 mb-6">
                        <h2 class="text-xl font-bold text-primary-dark mb-6">Overall Household Recovery Progress Breakdown</h2>
                        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
                           
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                               
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Damage Status Distribution</h3>
                                    <div id="damage-distribution-container">
                                        <p class="text-gray-400 text-sm">Loading data...</p>
                                    </div>
                                </div>
                               
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Overall Aid Fulfillment Rate</h3>
                                    <div class="flex justify-center items-center h-48 relative">
                                        <div class="w-40 h-40 rounded-full bg-gray-200 relative">
                                            <svg class="transform -rotate-90 w-40 h-40 absolute">
                                                <circle cx="80" cy="80" r="60" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200"/>
                                                <circle id="aidFulfillmentCircle" cx="80" cy="80" r="60" stroke="currentColor" stroke-width="8" fill="transparent" 
                                                    stroke-dasharray="377" stroke-dashoffset="94" class="text-primary-light transition-all duration-500" 
                                                    stroke-linecap="round"/>
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <div class="w-32 h-32 rounded-full flex flex-col items-center justify-center shadow-lg">
                                                    <p class="text-4xl font-bold text-primary-light" id="aidFulfillmentRate">0%</p>
                                                    <p class="text-xs text-gray-500">Fulfilled</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-center space-x-6 mt-4 text-sm">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-primary-light rounded-full mr-2"></span>
                                            <span class="text-gray-700" id="aidFulfillmentRateText">Needs Met (0%)</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-gray-200 rounded-full mr-2"></span>
                                            <span class="text-gray-700" id="aidFulfillmentRemainingText">Remaining Need (100%)</span>
                                        </div>
                                    </div>
                                </div>
                               
                            </div>
                            </div>
                    </div>

                    <!-- AI Suggestions & Recommendations Section -->
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-8 rounded-lg border border-indigo-200 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-indigo-900">AI Suggestions & Recommendations</h2>
                                <p class="text-xs text-indigo-600">Data-driven insights for recovery operations</p>
                            </div>
                        </div>
                        <div id="aiSuggestionsContainer" class="space-y-3">
                            <p class="text-gray-500 text-sm italic">Analyzing data...</p>
                        </div>
                    </div>

                </section>

                <section id="HouseholdProfiles" class="content-section hidden">
                   
                    <div id="saveMessage" class="hidden p-3 rounded-md text-sm mb-4"></div>

                    <form id="householdProfileForm" onsubmit="saveProfile(event)">
                       
                        <div class="relative -mb-px z-10">
                            <div class="inline-block px-6 pt-3 pb-2 bg-gradient-to-r from-primary-dark to-primary-light text-white font-semibold text-xl border border-primary-dark border-b-0 rounded-t-md shadow-lg">
                                Household Profiling Form
                            </div>
                        </div>
                       
                        <div class="bg-gray-50 p-8 rounded-b-lg rounded-tr-lg rounded-tl-none border border-gray-200 space-y-6">
                           
                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <h3 class="text-xl font-bold text-primary-dark mb-4 border-b pb-2">Household Head & Location Details</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 required mb-2">Head of Household Name</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <input type="text" id="headSurname" name="headSurname" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="Surname (e.g., Lapid)">
                                            </div>
                                            <div>
                                                <input type="text" id="headFirstname" name="headFirstname" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="First Name (e.g., Kyle)">
                                            </div>
                                            <div>
                                                <input type="text" id="headMiddleInitial" name="headMiddleInitial" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="Middle Initial (e.g., G.)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
								<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
									<div>
										<label for="headAge" class="block text-sm font-medium text-gray-700 required">Age</label>
										<input type="number" id="headAge" name="headAge" required min="1" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="e.g., 21">
									</div>

									<div>
										<label for="headGender" class="block text-sm font-medium text-gray-700 required">Gender</label>
										<select id="headGender" name="headGender" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
											<option value="">Select Gender</option>
											<option value="Male">Male</option>
											<option value="Female">Female</option>
										</select>
									</div>

									<div>
										<label for="contact" class="block text-sm font-medium text-gray-700 required">Contact Number</label>
										<input type="tel" id="contact" name="contact" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="e.g., 09123456789" pattern="[0-9]*" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"">
									</div>
								</div>

								<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
									
									<div>
										<label for="purok" class="block text-sm font-medium text-gray-700 required">Purok/Sitio</label>
										<select id="purok" name="purok" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
											<option value="">Select Purok/Sitio</option>
											<option value="Purok 1">Purok 1</option>
											<option value="Purok 2">Purok 2</option>
										</select>
									</div>
									
									<div>
										<label for="headCondition" class="block text-sm font-medium text-gray-700 required">Post-Disaster Condition</label>
										<select id="headCondition" name="headCondition" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
											<option value="">Select Status</option>
											<option value="Alive">Alive</option>
											<option value="Injured">Injured</option>
											<option value="Missing">Missing</option>
											<option value="Deceased">Deceased</option>
										</select>
									</div>
									
									<div>
										<label for="headLivelihood" class="block text-sm font-medium text-gray-700 required">Livelihood Status</label>
										<select id="headLivelihood" name="headLivelihood" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
											<option value="">Select Status</option>
											<option value="Employed">Employed</option>
											<option value="Self-Employed">Self-Employed</option>
											<option value="Unemployed">Unemployed</option>
											<option value="Retired">Retired</option>                                          
										</select>
									</div>
                                    
                                    <div></div>
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <h3 class="text-xl font-bold text-primary-dark mb-4 border-b pb-2">Family Members</h3>
                               
                                <div id="familyMembersContainer" class="mb-4">
                                    </div>

                                <button type="button" onclick="addMember()" class="text-sm font-semibold text-primary-light hover:text-primary-dark flex items-center transition">
                                    <i data-lucide="plus-circle" class="w-5 h-5 mr-1"></i> Add Another Family Member
                                </button>
                            </div>
                           
                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <h3 class="text-xl font-bold text-primary-dark mb-4 border-b pb-2">Disaster Damage Assessment</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="damageStatus" class="block text-sm font-medium text-gray-700 required">Damage Status (Percentage of Loss)</label>
                                        <select id="damageStatus" name="damageStatus" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                            <option value="">Select Damage Level</option>
                                            <option value="0">0% - Unaffected/No Damage</option>
                                            <option value="25">25% - Minor Damage (Superficial)</option>
                                            <option value="50">50% - Moderate Damage (Half Loss)</option>
                                            <option value="75">75% - Major Damage (Structural Loss)</option>
                                            <option value="100">100% - Total Loss/Destroyed</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="initialNeeds" class="block text-sm font-medium text-gray-700">Initial Needs (Comma Separated)</label>
                                        <input type="text" id="initialNeeds" name="initialNeeds" placeholder="e.g., Food, Shelter Kit, Medicine" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                        <button type="submit" class="w-full bg-gradient-to-r from-primary-dark to-primary-light text-white px-4 py-3 rounded-md text-lg font-semibold hover:opacity-90 transition shadow-lg mt-6">
                            <i data-lucide="save" class="w-5 h-5 inline-block mr-2"></i> Save Household Profile
                        </button>
                    </form>
                   
                    <h2 class="text-2xl font-semibold mt-10 mb-4 text-gray-800">Master List of Affected Households</h2>
                    
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 p-4 border border-gray-200 rounded-md bg-white/70">
                        
                        <div class="md:col-span-2">
                            <label for="masterlistSearch" class="block text-sm font-medium text-gray-700">Search Household (Surname)</label>
                            <input type="text" id="masterlistSearch" oninput="filterAndSearchHouseholdList()" placeholder="Enter surname..." 
                                class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                        </div>

                        <div>
                            <label for="masterlistPurokFilter" class="block text-sm font-medium text-gray-700">Filter by Purok/Sitio</label>
                            <select id="masterlistPurokFilter" onchange="filterAndSearchHouseholdList()" 
                                    class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                <option value="">All Purok/Sitio</option>
                                <option value="Purok 1">Purok 1</option>
                                <option value="Purok 2">Purok 2</option>
                            </select>
                        </div>

                        <div>
                            <label for="masterlistDamageFilter" class="block text-sm font-medium text-gray-700">Filter by Damage Status</label>
                            <select id="masterlistDamageFilter" onchange="filterAndSearchHouseholdList()" 
                                    class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                <option value="">All Damage Status</option>
                                <option value="0">0% - Unaffected</option>
                                <option value="25">25% - Minor Damage</option>
                                <option value="50">50% - Moderate Damage</option>
                                <option value="75">75% - Major Damage</option>
                                <option value="100">100% - Total Loss</option>
                            </select>
                        </div>
                    </div>
                    <div class="bg-white p-0 rounded-lg shadow-md border border-gray-100 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Head of Household</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purok/Sitio</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Damage Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                                    <th scope="col" scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="householdTableBody" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="6" class="p-4 text-center text-gray-400">Loading household data...</td></tr>
                            </tbody>
                        </table>
                    </div>

                </section>

                <section id="AidDistributionRecords" class="content-section hidden">
                   
                    <div id="aidSaveMessage" class="hidden p-3 rounded-md text-sm mb-4"></div>

                    <form id="aidDistributionForm" onsubmit="saveAidRecord(event)">
                       
                        <div class="relative -mb-px z-10">
                            <div class="inline-block px-6 pt-3 pb-2 bg-gradient-to-r from-aid-color-dark to-aid-color text-white font-semibold text-xl border border-orange-600 border-b-0 rounded-t-md shadow-lg">
                                Record Aid Distribution
                            </div>
                        </div>
                       
                        <div class="bg-gray-50 p-8 rounded-b-lg rounded-tr-lg rounded-tl-none border border-gray-200">
                           
                            <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 space-y-6">
                               
                                <div class="flex items-center">
                                    <input type="checkbox" id="multiSelectToggle" onclick="toggleMultiSelect(this.checked)" class="h-4 w-4 text-primary-dark border-gray-300 rounded-md focus:ring-primary-light">
                                    <label for="multiSelectToggle" class="ml-2 block text-sm font-medium text-gray-700 font-semibold cursor-pointer">
                                        Select Multiple Households (Bulk Distribution)
                                    </label>
                                </div>
                               
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                   
                                    <div id="singleRecipientContainer" class="md:col-span-4">
                                        <label for="aidRecipientId" class="block text-sm font-medium text-gray-700 required">Recipient Household (Head Name)</label>
                                        <select id="aidRecipientId" name="aidRecipientId" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                            <option value="">Select Single Household Head</option>
                                        </select>
                                    </div>
                                   
                                    <div id="multiRecipientContainer" class="md:col-span-4 hidden">
                                        <label class="block text-sm font-medium text-gray-700 required">Select Recipients (Check All That Apply)</label>
                                        <div id="multiSelectCheckboxes" class="mt-1 w-full p-2 border border-gray-300 rounded-md overflow-y-auto h-40 bg-gray-50">
                                            <p class="text-xs text-gray-400 p-2">No households profiled yet.</p>
                                        </div>
                                        <button type="button" onclick="selectAllRecipients()" class="mt-2 text-xs font-semibold text-primary-light hover:text-primary-dark transition">
                                            Select/Deselect All Visible
                                        </button>
                                    </div>
                                   
                                    <div class="md:col-span-1">
                                        <label for="aidType" class="block text-sm font-medium text-gray-700 required">Type of Aid</label>
                                        <select id="aidType" name="aidType" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                            <option value="">Select Aid Type</option>
                                            <option value="Food Pack">Food Pack</option>
                                            <option value="Shelter Kit">Shelter Kit (Tarpaulin/Lumber)</option>
                                            <option value="Cash Aid">Cash Aid</option>
                                            <option value="Hygiene Kit">Hygiene Kit</option>
                                            <option value="Medicine">Medicine</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-1">
                                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity / Value</label>
                                        <input type="text" id="quantity" name="quantity" placeholder="e.g., 1 box or Php 5,000" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                    </div>
                                   
                                    <div class="md:col-span-1">
                                        <label for="dateDistributed" class="block text-sm font-medium text-gray-700 required">Date Distributed</label>
                                        <input type="date" id="dateDistributed" name="dateDistributed" required class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                    </div>

                                    <div class="md:col-span-1">
                                        <label for="distributedBy" class="block text-sm font-medium text-gray-700">Distributed By (Optional)</label>
                                        <input type="text" id="distributedBy" name="distributedBy" placeholder="e.g., MSWD or LGU" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                    </div>
                                   
                                    <div class="md:col-span-4">
                                        <label for="distributionNotes" class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
                                        <input type="text" id="distributionNotes" name="distributionNotes" placeholder="e.g., Given to spouse/received 2nd tranche" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                    </div>

                                </div>
                            </div> </div> 
                        <button type="submit" class="w-full bg-gradient-to-r from-aid-color-dark to-aid-color text-white px-4 py-3 rounded-md text-lg font-semibold hover:opacity-90 transition shadow-lg mt-6">
                            <i data-lucide="hand-heart" class="w-5 h-5 inline-block mr-2"></i> Record Aid Distribution
                        </button>
                    </form>
                   
                    <h2 class="text-2xl font-semibold mt-10 mb-4 text-gray-800">Distribution Record</h2>
                    
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 p-4 border border-gray-200 rounded-md bg-white/70">
                        
                        <div class="md:col-span-2">
                            <label for="aidlistSearch" class="block text-sm font-medium text-gray-700">Search (Recipient Surname)</label>
                            <input type="text" id="aidlistSearch" oninput="filterAndSearchAidList()" placeholder="Enter surname..." 
                                class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                        </div>

                        <div>
                            <label for="aidlistAidTypeFilter" class="block text-sm font-medium text-gray-700">Filter by Aid Type</label>
                            <select id="aidlistAidTypeFilter" onchange="filterAndSearchAidList()" 
                                    class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                <option value="">All Aid Types</option>
                                <option value="Food Pack">Food Pack</option>
                                <option value="Shelter Kit">Shelter Kit</option>
                                <option value="Cash Aid">Cash Aid</option>
                                <option value="Hygiene Kit">Hygiene Kit</option>
                                <option value="Medicine">Medicine</option>
                            </select>
                        </div>

                        <div>
                            <label for="aidlistRecipientFilter" class="block text-sm font-medium text-gray-700">Filter by Recipient Household</label>
                            <select id="aidlistRecipientFilter" onchange="filterAndSearchAidList()" 
                                    class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                <option value="">All Recipients</option>
                                </select>
                        </div>
                    </div>
                    <div class="bg-white p-0 rounded-lg shadow-md border border-gray-100 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient (Head Name)</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aid Type</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="aidRecordTableBody" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="6" class="p-4 text-center text-gray-400">Loading aid distribution data...</td></tr>
                            </tbody>
                        </table>
                    </div>

                </section>

                <section id="ReportsExports" class="content-section hidden">
                   
                    <div id="reportSaveMessage" class="hidden p-3 rounded-md text-sm mb-4"></div>

                    <div class="relative -mb-px z-10">
                        <div class="inline-block px-6 pt-3 pb-2 bg-gradient-to-r from-report-color-dark to-report-color text-white font-semibold text-xl border border-report-color border-b-0 rounded-t-md shadow-lg">
                            Generate Official Reports
                        </div>
                    </div>
                   
                    <div class="bg-gray-50 p-8 rounded-b-lg rounded-tr-lg rounded-tl-none border border-gray-200">
                        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 space-y-6">
                       
                            <p class="text-lg font-medium text-gray-700 mb-4 border-b pb-2">Filter Data for Reporting</p>
                           
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label for="reportPurokFilter" class="block text-sm font-medium text-gray-700">Filter by Purok/Sitio</label>
                                    <select id="reportPurokFilter" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                        <option value="">All Purok/Sitio</option>
                                        <option value="Purok 1">Purok 1</option>
                                        <option value="Purok 2">Purok 2</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="reportDamageFilter" class="block text-sm font-medium text-gray-700">Filter by Damage Status</label>
                                    <select id="reportDamageFilter" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                        <option value="">All Damage Status</option>
                                        <option value="0">0% - Unaffected</option>
                                        <option value="25">25% - Minor Damage</option>
                                        <option value="50">50% - Moderate Damage</option>
                                        <option value="75">75% - Major Damage</option>
                                        <option value="100">100% - Total Loss</option>
                                    </select>
                                </div>
                               
                                <div></div>
                            </div>

                            <p class="text-lg font-medium text-gray-700 mb-4 border-b pb-2">Generate Report Formats</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <button onclick="generateReport('PDF')" class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-xl hover:border-red-700 hover:shadow-lg transition-all duration-200">
                                    <i data-lucide="file-text" class="w-10 h-10 text-gray-400 group-hover:text-red-700 mb-3 transition-colors"></i>
                                    <span class="font-semibold text-gray-700 group-hover:text-red-700 transition-colors">Generate PDF Report</span>
                                    <span class="text-xs text-gray-400 mt-1">Print-ready official document</span>
                                </button>
                                <button onclick="generateReport('Excel')" class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-xl hover:border-green-600 hover:shadow-lg transition-all duration-200">
                                    <i data-lucide="table" class="w-10 h-10 text-gray-400 group-hover:text-green-600 mb-3 transition-colors"></i>
                                    <span class="font-semibold text-gray-700 group-hover:text-green-600 transition-colors">Export to Excel</span>
                                    <span class="text-xs text-gray-400 mt-1">.XLSX format for analysis</span>
                                </button>
                                <button onclick="generateReport('CSV')" class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-xl hover:border-primary-dark hover:shadow-lg transition-all duration-200">
                                    <i data-lucide="download" class="w-10 h-10 text-gray-400 group-hover:text-primary-dark mb-3 transition-colors"></i>
                                    <span class="font-semibold text-gray-700 group-hover:text-primary-dark transition-colors">Export Raw CSV</span>
                                    <span class="text-xs text-gray-400 mt-1">Comma-separated values</span>
                                </button>
                            </div>
                           
                            <div id="reportOutput" class="hidden mt-6 p-4 rounded-md text-sm font-medium transition"></div>

                        </div> </div> </section>

                <script>
                    lucide.createIcons();
                </script>
            </main>
        </div>
    </div>
    <!-- Universal Modal -->
    <div id="universalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 invisible opacity-0 transition-all duration-300">
        <div id="modalContentWrapper" class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4 transform transition-all p-6 relative">
            <button id="closeModalButton" onclick="hideModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 focus:outline-none">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <div id="modalBody">
                <!-- Modal content injected via JS -->
            </div>
        </div>
    </div>
</body>
</html>