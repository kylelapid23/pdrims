<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDRIMS - Barangay Recovery System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
                        'report-color-dark': '#6a0000'
                    }
                }
            }
        }
    </script>
    <script>
        // Session Check
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
        if (!currentUser || currentUser.role !== 'viewer') {
            window.location.href = 'landing.php';
        }

        document.addEventListener('DOMContentLoaded', () => {
             // Update User Info in Sidebar/Header
             const userName = currentUser ? currentUser.name : 'Resident';
             const userId = currentUser ? String(currentUser.id) : 'N/A';
             
             // Sidebar
             const idDisplay = document.getElementById('userIdDisplay');
             if (idDisplay) {
                 idDisplay.innerHTML = `User ID: <strong class="text-white">user_${userId}</strong>`;
             }
             
             // Can add more specific UI updates here if elements exist
             console.log("Viewer Dashboard Loaded for:", userName);
        });
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

        // --- Data Initialization ---
        const HOUSEHOLDS_DATA = [
            { id: '001', headName: 'Bacsarsa, Vin Ysl M.', purok: 'Purok 1', damageStatus: '100', headAge: '45', contactNumber: '0917-123-4567', familyMembers: [{}, {}] },
            { id: '002', headName: 'Esio, Jazcel A.', purok: 'Purok 1', damageStatus: '50', headAge: '32', contactNumber: '0918-987-6543', familyMembers: [{}] },
            { id: '003', headName: 'Lapid, Kyle Grant G.', purok: 'Purok 2', damageStatus: '75', headAge: '58', contactNumber: '0999-000-1111', familyMembers: [{}, {}, {}] },
            { id: '004', headName: 'Severino, Nicole I.', purok: 'Purok 2', damageStatus: '75', headAge: '28', contactNumber: '0920-555-2222', familyMembers: [{}, {}, {}] },
        ];
       
        const AID_RECORDS_DATA = [
            { id: 'aid-001', aidRecipientId: '001', aidType: 'Food Pack', quantity: '1 box', dateDistributed: '2025-11-20', distributedBy: 'LGU', distributionNotes: 'Received by the head of the household.' },
            { id: 'aid-002', aidRecipientId: '003', aidType: 'Cash Aid', quantity: 'Php 5,000', dateDistributed: '2025-11-21', distributedBy: 'MSWD', distributionNotes: 'First tranche of financial assistance.' },
            { id: 'aid-003', aidRecipientId: '001', aidType: 'Shelter Kit', quantity: '1 pc', dateDistributed: '2025-11-25', distributedBy: 'NGO Relief', distributionNotes: 'Tarpaulin and basic tools.' }
        ];

        const updateDashboardMetrics = () => {
             fetch('api_dashboard_stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        console.error("Error fetching dashboard stats:", data.error);
                        return;
                    }

                    // 1. Key Metrics
                    document.getElementById('stat-profiled-count').textContent = data.totalProfiled || '0';
                    document.getElementById('stat-destroyed-count').textContent = data.totalDestroyed || '0';
                    document.getElementById('stat-priority-count').textContent = data.totalPriority || '0';
                    document.getElementById('stat-recovery-percent').textContent = `${data.recoveryPercent || 0}%`;

                    // 2. Bar Chart (Purok Stats)
                    // We need to normalize heights based on max count
                    const purokStats = data.purokStats || {};
                    const purokLabels = Object.keys(purokStats);
                    const counts = Object.values(purokStats);
                    const maxCount = Math.max(...counts, 1); // Avoid div by zero

                    // We need to inject the HTML for bars dynamically or update existing ones.
                    // The existing HTML had hardcoded 3 bars. Let's rebuild the container content.
                    // Find the container for the bars.
                    // Since we can't easily target the specific div inside the generic structure without an ID,
                    // we might need to modify the HTML to add an ID to the chart container first.
                    // CHECK: I see I can't modify HTML structure here easily without replacing huge block.
                    // I will target the container relative to the header "Household Damage Breakdown by Purok".
                    
                    const chartTitle = Array.from(document.querySelectorAll('h3')).find(el => el.textContent.includes('Household Damage Breakdown by Purok'));
                    if (chartTitle) {
                        const chartContainer = chartTitle.nextElementSibling;
                        if (chartContainer) {
                            // Colors for bars loop
                            const colors = ['bg-red-600', 'bg-yellow-600', 'bg-green-600', 'bg-blue-600', 'bg-purple-600'];
                            
                            let barsHTML = '';
                            purokLabels.forEach((purok, index) => {
                                const count = purokStats[purok];
                                const heightPercent = Math.round((count / maxCount) * 80) + 20; // Min 20% height for visibility
                                const color = colors[index % colors.length];
                                
                                barsHTML += `
                                    <div class="relative flex flex-col items-center justify-end w-1/4 h-[${heightPercent}%] ${color} hover:opacity-90 transition duration-150 rounded-t-lg shadow-md" style="height: ${heightPercent}%">
                                        <span class="absolute -top-6 text-xs font-medium text-gray-700">${count}</span>
                                        <span class="text-xs text-white p-1 text-center truncate w-full">${purok}</span>
                                    </div>
                                `;
                            });
                            
                            if (purokLabels.length === 0) {
                                barsHTML = '<div class="text-xs text-gray-400 w-full text-center self-end pb-4">No data available</div>';
                            }

                            chartContainer.innerHTML = barsHTML;
                        }
                    }

                    // 3. Circle Chart (Aid fulfillment)
                    const fulfillmentRate = data.recoveryPercent || 0;
                    const remainingRate = 100 - fulfillmentRate;
                    
                    // Similarly, find the container relative to title "Overall Aid Fulfillment Rate"
                    const pieTitle = Array.from(document.querySelectorAll('h3')).find(el => el.textContent.includes('Overall Aid Fulfillment Rate'));
                    if (pieTitle) {
                        const pieContainer = pieTitle.nextElementSibling;
                         // We can update the pie visual via conic gradient
                         // The existing HTML used a complex nested div structure.
                         // Simplest is to replace the innerHTML of the chart wrapper.
                         
                         pieContainer.innerHTML = `
                            <div class="w-40 h-40 rounded-full bg-gray-200 relative" style="background: conic-gradient(#0fa968 ${fulfillmentRate}%, #e5e7eb ${fulfillmentRate}% 100%);">
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <div class="w-32 h-32 rounded-full bg-white flex flex-col items-center justify-center shadow-inner">
                                        <p class="text-4xl font-bold text-primary-light">${fulfillmentRate}%</p>
                                        <p class="text-xs text-gray-500">Fulfilled</p>
                                    </div>
                                </div>
                            </div>
                         `;
                         
                         // Update legend if it exists (next element)
                         const legend = pieContainer.nextElementSibling;
                         if (legend) {
                             legend.innerHTML = `
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-primary-light rounded-full mr-2"></span>
                                    <span class="text-gray-700">Needs Met (${fulfillmentRate}%)</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-gray-200 rounded-full mr-2"></span>
                                    <span class="text-gray-700">Remaining Need (${remainingRate}%)</span>
                                </div>
                             `;
                         }
                    }

                })
                .catch(err => console.error("Failed to load dashboard metrics", err));
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
                ? memberList.map((m, i) => `<li class="text-gray-700">Member ${i + 1}</li>`).join('')
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
            document.getElementById('detailsDrawer').classList.add('drawer-open');
            
            closeAidDetailsDrawer();
            lucide.createIcons();
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
            if (!household) return alert('Household not found for editing.');

            const messageElement = document.getElementById('saveMessage');
            messageElement.className = 'mt-4 p-3 rounded-md text-sm font-medium transition bg-yellow-100 text-yellow-800';
            messageElement.textContent = `Pre-filling form for EDITING Household ID: ${householdId} (${household.headName}).`;

            setTimeout(() => {
                 messageElement.classList.add('hidden');
            }, 8000);
            
            document.querySelectorAll('.member-row').forEach(row => row.remove());
            window.addMember();
            filterAndSearchHouseholdList(); 
        };
        
        // --- HOUSEHOLD MASTERLIST FILTER AND SEARCH LOGIC ---
		
        window.filterAndSearchHouseholdList = () => {
            const searchText = (document.getElementById('masterlistSearch')?.value || '').toLowerCase();
            const purokFilter = document.getElementById('masterlistPurokFilter')?.value || '';
            const damageFilter = document.getElementById('masterlistDamageFilter')?.value || '';

            const filteredHouseholds = HOUSEHOLDS_DATA.filter(household => {
                const matchesSearch = !searchText || 
                    (household.headName && household.headName.toLowerCase().includes(searchText)) ||
                    (household.id && household.id.toLowerCase().includes(searchText));
                const matchesPurok = !purokFilter || household.purok === purokFilter;
                const matchesDamage = !damageFilter || household.damageStatus === damageFilter;

                return matchesSearch && matchesPurok && matchesDamage;
            });

            renderHouseholdList(filteredHouseholds);
        };

        // --- HOUSEHOLD MASTERLIST RENDER ---
		
        window.renderHouseholdList = (data) => {
            const tableBody = document.getElementById('householdTableBody');
            if (!tableBody) return;
           
            const householdsToRender = data.length > 0 ? data : HOUSEHOLDS_DATA; 

            if (householdsToRender.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400">No matching household profiles found. Adjust your filters or search term.</td></tr>`;
                return;
            }

            const rowsHTML = householdsToRender.map((household, index) => {
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
            const aidRecord = AID_RECORDS_DATA.find(r => r.id === aidId);
            if (!aidRecord) return alert('Aid Record not found for ID: ' + aidId);

            const household = HOUSEHOLDS_DATA.find(h => h.id === aidRecord.aidRecipientId);
            const headName = household ? household.headName : 'Household Not Found';
            const purok = household ? household.purok : 'N/A';

            setText('aidDrawerRecipient', headName);
            setText('aidDrawerId', aidRecord.id);
            setText('aidDrawerHouseholdId', aidRecord.aidRecipientId);
            setText('aidDrawerPurok', purok);
            setText('aidDrawerType', aidRecord.aidType);
            setText('aidDrawerQuantity', aidRecord.quantity || '1');
            setText('aidDrawerDate', aidRecord.dateDistributed);
            setText('aidDrawerBy', aidRecord.distributedBy || 'N/A');
            setText('aidDrawerNotes', aidRecord.distributionNotes || 'None specified.');

            document.getElementById('aidDrawerEditButton').setAttribute('onclick', `startEditAidRecord('${aidRecord.id}')`);
            
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

            const aidRecord = AID_RECORDS_DATA.find(r => r.id === aidId);
            if (!aidRecord) return alert('Aid record not found for editing.');

            const messageElement = document.getElementById('aidSaveMessage');
            messageElement.className = 'mt-4 p-3 rounded-md text-sm font-medium transition bg-yellow-100 text-yellow-800';
            messageElement.textContent = `Pre-filling form for EDITING Aid Record ID: ${aidId}.`;

            // Pre-fill form (only single select is supported for edit pre-fill)
            document.getElementById('multiSelectToggle').checked = false;
            window.toggleMultiSelect(false);
            document.getElementById('aidRecipientId').value = aidRecord.aidRecipientId;
            document.getElementById('aidType').value = aidRecord.aidType;
            document.getElementById('quantity').value = aidRecord.quantity;
            document.getElementById('dateDistributed').value = aidRecord.dateDistributed;
            document.getElementById('distributedBy').value = aidRecord.distributedBy || '';
            document.getElementById('distributionNotes').value = aidRecord.distributionNotes || '';


            setTimeout(() => {
                 messageElement.classList.add('hidden');
            }, 8000);
        };
        
        window.filterAndSearchAidList = () => {
            const searchText = (document.getElementById('aidlistSearch')?.value || '').toLowerCase();
            const aidTypeFilter = document.getElementById('aidlistAidTypeFilter')?.value || '';
            const recipientIdFilter = document.getElementById('aidlistRecipientFilter')?.value || '';

            const filteredAidRecords = AID_RECORDS_DATA.filter(record => {
                const household = HOUSEHOLDS_DATA.find(h => h.id === record.aidRecipientId) || {};
                const headName = household.headName ? household.headName.toLowerCase() : '';

                // Search Filter 
                const matchesSearch = !searchText || 
                    headName.includes(searchText) ||
                    (record.aidType && record.aidType.toLowerCase().includes(searchText)) ||
                    (record.id && record.id.toLowerCase().includes(searchText));

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
            const recordsToRender = data.length > 0 ? data : AID_RECORDS_DATA;

            if (recordsToRender.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400">No matching aid distribution records found.</td></tr>`;
                return;
            }

            const rowsHTML = recordsToRender.map((record, index) => {
                const household = HOUSEHOLDS_DATA.find(h => h.id === record.aidRecipientId);
                const headName = household ? household.headName : 'Household Not Found';
                const date = record.dateDistributed || 'N/A';

                return `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-semibold text-gray-700">${index + 1}</td>
                        <td class="p-4 text-sm">${headName} (${record.aidRecipientId.substring(0, 8)})</td>
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
       
        // --- MODAL & NOTIFICATION FUNCTIONS ---

        const universalModal = document.getElementById('universalModal');
        const modalContentWrapper = document.getElementById('modalContentWrapper');
        const modalBody = document.getElementById('modalBody');

        window.showModal = (contentHTML, maxWidthClass = 'max-w-xl') => {
            modalBody.innerHTML = contentHTML;
            document.body.classList.add('overflow-hidden'); 

            const maxWClasses = ['max-w-xl', 'max-w-3xl', 'max-w-4xl', 'max-w-2xl', 'max-w-lg', 'max-w-md']; 
            if (modalContentWrapper) {
                 maxWClasses.forEach(c => modalContentWrapper.classList.remove(c));
                 modalContentWrapper.classList.add(maxWidthClass);
            }
            
            universalModal.classList.remove('invisible', 'opacity-0');
            universalModal.classList.add('visible', 'opacity-100');
            lucide.createIcons(); 
        };

        window.hideModal = () => {
            document.body.classList.remove('overflow-hidden');
            universalModal.classList.remove('visible', 'opacity-100');
            universalModal.classList.add('invisible', 'opacity-0');
            setTimeout(() => { modalBody.innerHTML = ''; }, 300);
        };

        window.alertMessage = (message, type = 'info') => {
            const colors = {
                info: { bg: 'bg-blue-50', border: 'border-blue-500', text: 'text-blue-800' },
                error: { bg: 'bg-red-50', border: 'border-red-500', text: 'text-red-800' }
            };
            const style = colors[type];
            showModal(`
                <h2 class="text-2xl font-bold ${style.text} mb-2">${type === 'error' ? 'Error' : 'Notification'}</h2>
                <p class="text-sm text-gray-500 mb-4">System Message:</p>
                <div class="p-4 ${style.bg} rounded-lg border-l-4 ${style.border}">
                    <p class="${style.text}">${message}</p>
                </div>
                <button onclick="hideModal()" class="mt-6 w-full bg-gray-500 text-white font-semibold py-2 rounded-lg hover:bg-gray-600 transition">
                    Dismiss
                </button>
            `, 'max-w-md');
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
           
            if (window.innerWidth < 1024 && isSidebarOpen) {
                window.toggleSidebar();
            }
        };

        const MEMBER_ROW_HTML = (iconHtml) => `
            <div class="col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Name of the Household Member</label>
                    <input type="text" id="memberSurname" placeholder="Surname (e.g., Bacsarsa)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">  </label>
                    <input type="text" id="memberFirstName" placeholder="First Name (e.g., Vin)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">  </label>
                    <input type="text" id="memberMiddleInitial" placeholder="Middle Initial (e.g., M)" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 required">  </label>
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
            const form = event.target;
           
            const messageElement = document.getElementById('saveMessage');
           
            messageElement.textContent = 'Profile saved successfully!';
            messageElement.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');
            messageElement.classList.add('bg-green-100', 'text-green-800');

            form.reset();
            document.querySelectorAll('.member-row').forEach(row => row.remove());
            window.addMember();
            filterAndSearchHouseholdList();

            setTimeout(() => {
                 messageElement.classList.add('hidden');
            }, 5000);
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
                 messageElement.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                 messageElement.classList.add('bg-yellow-100', 'text-yellow-800');
                 setTimeout(() => { messageElement.classList.add('hidden'); }, 5000);
                 return;
            }
           
            const count = selectedIds.length;
            messageElement.textContent = `Successfully saved ${count} aid distribution record(s)!`;
            messageElement.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');
            messageElement.classList.add('bg-green-100', 'text-green-800');
           
            if (isMultiSelect) {
                document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                document.getElementById('aidRecipientId').value = '';
            }
            form.querySelector('#aidType').value = '';
            form.querySelector('#quantity').value = '';
            form.querySelector('#distributionNotes').value = '';


            filterAndSearchAidList(); // Re-render the list

            messageElement.classList.remove('hidden');
            setTimeout(() => { messageElement.classList.add('hidden'); }, 7000);
        };

        window.generateReport = (format) => {
            const filterPurok = document.getElementById('reportPurokFilter').value;
            const filterDamage = document.getElementById('reportDamageFilter').value;
           
            const filteredCount = 50;

            const resultElement = document.getElementById('reportOutput');
            resultElement.classList.remove('hidden', 'bg-green-100', 'bg-red-100', 'text-green-800', 'text-red-800');
           
            resultElement.textContent = `Generated ${format} report for ${filteredCount} households matching criteria.`;
            resultElement.classList.add('bg-green-100', 'text-green-800');

            setTimeout(() => { resultElement.classList.add('hidden'); }, 7000);
        };

        window.confirmLogout = () => {
             showModal(`
                <h2 class="text-2xl font-bold text-red-600 mb-2">Confirm Logout</h2>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to sign out of the system?</p>
                <div class="flex gap-3 mt-6">
                    <button onclick="hideModal()" class="flex-1 bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                    <button onclick="performLogout()" class="flex-1 bg-red-600 text-white font-semibold py-2 rounded-lg hover:bg-red-700 transition shadow-md">
                        Logout
                    </button>
                </div>
            `, 'max-w-md');
        };

        window.performLogout = () => {
            localStorage.removeItem('currentUser');
            window.location.href = 'landing.php';
        };

        window.showRestrictedModal = () => {
            showModal(`
                <h2 class="text-2xl font-bold text-red-600 mb-2">Restricted Access</h2>
                <div class="p-4 bg-red-50 rounded-lg border-l-4 border-red-500 mb-4">
                    <p class="text-red-800 font-medium">Authorized Access Only</p>
                    <p class="text-sm text-red-600 mt-1">This detailed list is available only for Barangay Officials.</p>
                </div>
                <div class="flex justify-end">
                    <button onclick="hideModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Close
                    </button>
                </div>
            `, 'max-w-md');
        };

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
                        
                        // Restricted Sections for Viewers
                        const restrictedSections = ['HouseholdProfiles', 'AidDistributionRecords', 'ReportsExports'];
                        if (restrictedSections.includes(targetId)) {
                             window.showRestrictedModal();
                             return;
                        }

                        window.switchContent(targetId, link);
                    });
                }
            });

            document.getElementById('dateDistributed').value = new Date().toISOString().split('T')[0];

            updateDashboardMetrics();
            filterAndSearchHouseholdList();
            populateHouseholdDropdown(); 
            filterAndSearchAidList(); 
           
            // Removed hardcoded userIdDisplay overwrite to allow the actual ID from localStorage to persist
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
                
                <a href="#" onclick="confirmLogout()" class="nav-link mt-4 hover:bg-red-800/50">
                    <i data-lucide="log-out"></i>
                    Logout
                </a>
            </nav>

            <div class="p-6 text-xs text-white/70 border-t border-white/10">
                <p id="userIdDisplay">User ID: user-id-001</p>
                <p class="mt-1 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-green-400 mr-2"></span>
                    Viewer
                </p>
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

            <div class="border-t pt-4 mt-auto">
                <button id="drawerEditButton" onclick="startEditProfile('mock-id')" class="w-full bg-primary-light text-white px-4 py-2 rounded-md font-semibold hover:bg-primary-dark transition flex items-center justify-center shadow-lg">
                    <i data-lucide="edit" class="w-5 h-5 inline-block mr-2"></i> Edit Profile Data
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

            <div class="border-t pt-4 mt-auto">
                <button id="aidDrawerEditButton" onclick="startEditAidRecord('aid-id')" class="w-full bg-aid-color text-white px-4 py-2 rounded-md font-semibold hover:bg-aid-color-dark transition flex items-center justify-center shadow-lg">
                    <i data-lucide="edit" class="w-5 h-5 inline-block mr-2"></i> Edit Distribution Record
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
                                    <button onclick="showRestrictedModal()" class="bg-orange-500 text-white px-4 py-2 rounded-md text-sm hover:bg-orange-600 transition shadow-md whitespace-nowrap">
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
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Household Damage Breakdown by Purok</h3>
                                    <div class="flex items-end h-48 border-l border-b border-gray-300 px-2 space-x-6">
                                       
                                        <div class="relative flex flex-col items-center justify-end w-1/4 h-4/5 bg-red-600 hover:bg-red-700 transition duration-150 rounded-t-lg shadow-md">
                                            <span class="absolute -top-6 text-xs font-medium text-red-600">30</span>
                                            <span class="text-xs text-white p-1">Purok 1</span>
                                        </div>
                                        <div class="relative flex flex-col items-center justify-end w-1/4 h-3/5 bg-yellow-600 hover:bg-yellow-700 transition duration-150 rounded-t-lg shadow-md">
                                            <span class="absolute -top-6 text-xs font-medium text-yellow-600">22</span>
                                            <span class="text-xs text-white p-1">Purok 2</span>
                                        </div>
                                        <div class="relative flex flex-col items-center justify-end w-1/4 h-2/5 bg-green-600 hover:bg-green-700 transition duration-150 rounded-t-lg shadow-md">
                                            <span class="absolute -top-6 text-xs font-medium text-green-600">15</span>
                                            <span class="text-xs text-white p-1">Purok 3</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 text-center pt-2">X-Axis: Purok / Y-Axis: Households (Count)</p>
                                </div>
                               
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Overall Aid Fulfillment Rate</h3>
                                    <div class="flex justify-center items-center h-48 relative">
                                        <div class="w-40 h-40 rounded-full bg-gray-200 relative">
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <div class="w-32 h-32 rounded-full border-8 border-primary-light flex flex-col items-center justify-center shadow-lg">
                                                    <p class="text-4xl font-bold text-primary-light">75%</p>
                                                    <p class="text-xs text-gray-500">Fulfilled</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="flex justify-center space-x-6 mt-4 text-sm">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-primary-light rounded-full mr-2"></span>
                                            <span class="text-gray-700">Needs Met (75%)</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-gray-200 rounded-full mr-2"></span>
                                            <span class="text-gray-700">Remaining Need (25%)</span>
                                        </div>
                                    </div>
                                </div>
                               
                            </div>
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
										<input type="text" id="contact" name="contact" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" placeholder="e.g., 09123456789">
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
                            <label for="masterlistSearch" class="block text-sm font-medium text-gray-700">Search Household (Name or ID)</label>
                            <input type="text" id="masterlistSearch" oninput="filterAndSearchHouseholdList()" placeholder="Enter name or ID..." 
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
                            <label for="aidlistSearch" class="block text-sm font-medium text-gray-700">Search (Recipient Name, Aid Type, or ID)</label>
                            <input type="text" id="aidlistSearch" oninput="filterAndSearchAidList()" placeholder="Enter name, aid type, or ID..." 
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
                            <div class="flex flex-col sm:flex-row gap-4">
                                <button onclick="generateReport('CSV')" class="flex-1 bg-gradient-to-r from-primary-dark to-primary-light text-white px-6 py-3 rounded-md font-semibold hover:opacity-90 transition shadow-md">
                                    <i data-lucide="file-text" class="w-5 h-5 inline-block mr-2"></i> Export Raw Data (CSV)
                                </button>
                                <button onclick="generateReport('PDF')" class="flex-1 bg-gradient-to-r from-report-color-dark to-report-color text-white px-6 py-3 rounded-md font-semibold hover:opacity-90 transition shadow-md">
                                    <i data-lucide="download" class="w-5 h-5 inline-block mr-2"></i> Generate Summary (PDF)
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