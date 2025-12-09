<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDRIMS - Admin Management</title>
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
                        'admin-color': '#1e3a8a',
                        'admin-light': '#3b82f6',
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
        .w-70 { width: 280px; }
        .nav-link {
            display: flex; align-items: center; padding: 12px 24px; font-size: 1rem; font-weight: 500; color: #E0E7FF; transition: all 0.2s;
        }
        .nav-link:hover { background-color: rgba(0, 121, 107, 0.5); }
        .nav-link.active { background-color: #00796B; font-weight: 600; color: white; }
        .nav-link-icon { width: 20px; height: 20px; margin-right: 12px; }
        
        .admin-tab {
            cursor: pointer;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 1rem;
            border: 1px solid transparent;
            border-bottom: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .active-tab {
            background: linear-gradient(to right, #004D40, #00796B); 
            color: white;
            border-color: #004D40; 
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            margin-right: 4px; 
            position: relative;
            z-index: 20; 
        }

        .inactive-tab {
            background-color: #E5E7EB; 
            color: #4B5563;
            border-color: #D1D5DB; 
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            margin-right: 4px;
            border-bottom: 1px solid #D1D5DB;
            transform: translateY(1px); 
        }

        .inactive-tab:hover {
            color: #004D40; 
            background-color: #F3F4F6;
        }

        .pending-section-collapsible {
            max-height: 0;
            opacity: 0;
            margin-bottom: 0 !important;
            transition: max-height 0.5s ease-in-out, opacity 0.5s ease-in-out, margin-bottom 0.5s ease-in-out;
        }
        .pending-section-collapsible.expanded {
            max-height: 1000px; 
            opacity: 1;
            margin-bottom: 2rem !important; 
        }
        /* Custom CSS for the right-side drawer */
        .drawer {
            transition: transform 0.3s ease-out;
            transform: translateX(100%);
        }
        .drawer-open {
            transform: translateX(0);
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
   
    <script>
        let isSidebarOpen = false;

        let userToDeleteId = null;
        const MOCK_ADMIN_ID = "admin-001"; 

        let MOCK_USERS = []; // User data will be loaded from API

        let SYSTEM_LOGS = []; // Logs will be loaded from API

        // Helper to get current user correctly (Admin/Official -> sessionStorage, Viewer -> localStorage)
        function getCurrentUser() {
            const sessionUser = sessionStorage.getItem('currentUser');
            if (sessionUser) return JSON.parse(sessionUser);
            
            const localUser = localStorage.getItem('currentUser');
            if (localUser) return JSON.parse(localUser);
            
            return {};
        }

        // Toggle password visibility function
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }


        let MOCK_INBOX = [
            { id: 101, category: "Aid Inquiry", sender: "Vin Ysl M. Bacsarsa", subject: "Relief Goods Schedule", date: "2025-11-26", message: "Good morning, I would like to ask when the next batch of relief goods for Purok 2 will be distributed? We have not received the second tranche.", read: false }
        ];

        function getCategoryColorStyle(category) {
            if (category === 'Unrecorded Damage') return { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200' };
            if (category === 'Aid Inquiry') return { bg: 'bg-orange-100', text: 'text-orange-700', border: 'border-orange-200' };
            if (category === 'Profile Update') return { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200' };
            return { bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-200' };
        }

        function confirmLogout() {
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
        }

        function performLogout() {
            localStorage.removeItem('currentUser');
            window.location.href = 'landing.php';
        }


        function toggleSidebar() {
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
        }

        function switchContent(targetId, clickedElement) {
            document.querySelectorAll('.content-section').forEach(section => section.classList.add('hidden'));
            
            const targetSection = document.getElementById(targetId);
            if (targetSection) targetSection.classList.remove('hidden');

            const headerTitle = document.getElementById('mainTitle');
            if (headerTitle) headerTitle.textContent = clickedElement.textContent.trim();

            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            clickedElement.classList.add('active');

            if (window.innerWidth < 1024 && isSidebarOpen) toggleSidebar();
        }

        window.switchAdminTab = (targetId, clickedElement) => {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active-tab');
                tab.classList.add('inactive-tab');
                tab.style.borderBottom = '1px solid #D1D5DB';
            });

            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }
            
            clickedElement.classList.remove('inactive-tab');
            clickedElement.classList.add('active-tab');
            clickedElement.style.borderBottom = 'none';
        };
        
        function scrollToPendingAccounts() {
            const pendingSection = document.getElementById('pendingAccountsSection');
            if (pendingSection) {
                const isExpanded = pendingSection.classList.contains('expanded');
                
                if (isExpanded) {
                    pendingSection.classList.remove('expanded');
                    pendingSection.classList.add('pending-section-collapsible');
                } else {
                    pendingSection.classList.remove('pending-section-collapsible');
                    pendingSection.classList.add('expanded');
                }
            }
        }

        function generateUserRow(user) {
            const statusColor = user.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
            
            let actionButtons = '';
            if (user.status === 'Pending') {
                actionButtons = `
                    <button onclick="approveUser('${user.id}')" class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded border border-green-200 hover:bg-green-100 mr-2">
                        Approve
                    </button>`;
            } else {
                actionButtons = '';
            }
            
            actionButtons += `
                <button onclick="deleteUser('${user.id}')" class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded border border-red-200 hover:bg-red-100">
                    Delete
                </button>
            `;

            return `
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-4">
                        <p class="text-sm font-semibold text-gray-800">${user.name}</p>
                        <p class="text-xs text-gray-500">ID: ${user.id}</p>
                    </td>
                    <td class="p-4 text-sm text-gray-600">${user.email}</td>
                    <td class="p-4 text-sm">
                        <span class="block text-xs font-semibold text-gray-700">${user.role}</span>
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col items-start">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColor} mb-1">${user.status}</span>
                            <span class="text-[10px] text-gray-400">Active: ${user.lastActive}</span>
                        </div>
                    </td>
                    <td class="p-4 text-sm whitespace-nowrap">
                        ${actionButtons}
                    </td>
                </tr>
            `;
        }

        function fetchUsers() {
            fetch('api_admin_users.php')
                .then(res => res.json())
                .then(data => {
                    MOCK_USERS = data;
                    renderUserTable();
                })
                .catch(err => console.error('Error fetching users:', err));
        }

        function fetchLogs() {
             fetch('api_logs.php')
                .then(res => res.json())
                .then(data => {
                    if(data.error) {
                         console.error('Error fetching logs:', data.error);
                         return;
                    }
                    SYSTEM_LOGS = data;
                    renderLogsTable();
                })
                .catch(err => console.error('Error connecting to logs API:', err));
        }

        function logAction(action, target) {
             const currentUser = getCurrentUser();
             const userId = currentUser.id || 'N/A';
             const userName = currentUser.name || 'System Administrator';

             fetch('api_logs.php', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify({
                     user_id: userId,
                     user_name: userName,
                     action: action,
                     target: target
                 })
             })
             .then(res => res.json())
             .then(data => {
                 if (data.success) {
                     fetchLogs(); // Refresh logs after action
                 }
             })
             .catch(err => console.error('Error logging action:', err));
        }

        function fetchLogs() {
             fetch('api_logs.php')
                .then(res => res.json())
                .then(data => {
                    if(data.error) {
                        console.error('Error fetching logs:', data.error);
                        return;
                    }
                    SYSTEM_LOGS = data;
                    renderLogsTable();
                })
                .catch(err => console.error('Error connecting to logs API:', err));
        }

        function logAction(action, target) {
             const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
             const userId = currentUser.id || 'N/A';
             const userName = currentUser.name || 'System Administrator';

             fetch('api_logs.php', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify({
                     user_id: userId,
                     user_name: userName,
                     action: action,
                     target: target
                 })
             })
             .then(res => res.json())
             .then(data => {
                 if (data.success) {
                     fetchLogs(); // Refresh logs after action
                 }
             })
             .catch(err => console.error('Error logging action:', err));
        }

        function renderUserTable() {
            const pendingUsers = MOCK_USERS.filter(u => u.status === "Pending");
            const systemUsers = MOCK_USERS.filter(u => (u.type === "official") && u.status === "Active");
            const residentUsers = MOCK_USERS.filter(u => u.type === 'viewer' && u.status === "Active");

            const pendingBody = document.getElementById('pendingTableBody'); 
            const officialsBody = document.getElementById('officialsTableBody');
            const residentsBody = document.getElementById('residentsTableBody');
            const pendingCountButton = document.getElementById('pendingCountButton'); 
            const pendingSection = document.getElementById('pendingAccountsSection'); 

            pendingBody.innerHTML = ''; 
            officialsBody.innerHTML = '';
            residentsBody.innerHTML = '';

            if (pendingCountButton) {
                 pendingCountButton.textContent = pendingUsers.length;
            }

            if (pendingUsers.length > 0) {
                pendingUsers.forEach(user => {
                    pendingBody.innerHTML += generateUserRow(user);
                });
            } else {
                pendingBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500 bg-red-50">No accounts are currently pending approval.</td></tr>';
            }
            
            if(pendingSection) {
                 pendingSection.classList.remove('expanded');
                 pendingSection.classList.add('pending-section-collapsible');
            }

            if (systemUsers.length > 0) {
                systemUsers.forEach(user => {
                    officialsBody.innerHTML += generateUserRow(user);
                });
            } else {
                officialsBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No active official accounts found.</td></tr>';
            }

            if (residentUsers.length > 0) {
                residentUsers.forEach(user => {
                    residentsBody.innerHTML += generateUserRow(user);
                });
            } else {
                residentsBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No active resident viewer accounts found.</td></tr>';
            }
        }

        function deleteUser(id) {
            const user = MOCK_USERS.find(u => u.id === id);
            if (user) {
                userToDeleteId = id;
                document.getElementById('deleteUserName').textContent = user.name;
                document.getElementById('deleteConfirmationModal').classList.remove('hidden');
                document.getElementById('adminPasswordInput').value = ''; 
            }
        }

        function cancelDelete() {
            userToDeleteId = null;
            document.getElementById('adminPasswordInput').value = '';
            document.getElementById('deleteConfirmationModal').classList.add('hidden');
        }

        function confirmDeleteUser() {
            const password = document.getElementById('adminPasswordInput').value.trim();
            const currentUser = getCurrentUser();

            if (!password) {
                alertMessage("Please enter your password to confirm.", "error");
                return;
            }

            if (userToDeleteId === null) {
                alertMessage("Error: No user selected for deletion.", "error");
                cancelDelete();
                return;
            }
            
            fetch('api_admin_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'delete', 
                    id: userToDeleteId,
                    password: password 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alertMessage('User deleted successfully.', "info");
                    fetchUsers();
                    logAction("Deleted User", `User ID: ${userToDeleteId}`);
                } else {
                    alertMessage('Error deleting user: ' + (data.error || 'Unknown error'), "error");
                }
                cancelDelete();
            })
            .catch(err => {
                alertMessage('Connection error: ' + err.message, "error");
                cancelDelete();
            });
        }

        function renderLogsTable() {
            const tbody = document.getElementById('logsTableBody');
            tbody.innerHTML = '';
            
            if (SYSTEM_LOGS.length === 0) {
               tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No logs found.</td></tr>';
               return;
            }

            SYSTEM_LOGS.forEach(log => {
                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 transition text-sm">
                        <td class="p-3 font-mono text-gray-500 text-xs text-center border-r border-gray-100">${log.id}</td>
                        <td class="p-3 font-semibold text-gray-700 border-r border-gray-100">${log.user_name}</td>
                         <td class="p-3 border-r border-gray-100">
                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs border border-blue-100 font-medium">${log.action}</span>
                        </td>
                        <td class="p-3 text-gray-600 border-r border-gray-100">${log.target}</td>
                        <td class="p-3 text-gray-500 text-xs">${log.timestamp}</td>
                    </tr>
                `;
            });
        }

        // Filtered logs storage for export
        let FILTERED_LOGS = [];

        window.toggleLogDateInputs = () => {
            const filterType = document.getElementById('logFilterType').value;
            const singleDateContainer = document.getElementById('singleDateContainer');
            const dateRangeContainer = document.getElementById('dateRangeContainer');
            
            // Hide both containers first
            singleDateContainer.classList.add('hidden');
            singleDateContainer.classList.remove('flex');
            dateRangeContainer.classList.add('hidden');
            dateRangeContainer.classList.remove('flex');
            
            if (filterType === 'single') {
                singleDateContainer.classList.remove('hidden');
                singleDateContainer.classList.add('flex');
            } else if (filterType === 'range') {
                dateRangeContainer.classList.remove('hidden');
                dateRangeContainer.classList.add('flex');
            }
            lucide.createIcons();
        };

        window.resetLogFilter = () => {
            // Reset filter dropdown
            document.getElementById('logFilterType').value = 'all';
            
            // Clear date inputs
            document.getElementById('logSingleDate').value = '';
            document.getElementById('logStartDate').value = '';
            document.getElementById('logEndDate').value = '';
            
            // Hide date containers
            document.getElementById('singleDateContainer').classList.add('hidden');
            document.getElementById('singleDateContainer').classList.remove('flex');
            document.getElementById('dateRangeContainer').classList.add('hidden');
            document.getElementById('dateRangeContainer').classList.remove('flex');
            
            // Reset filtered logs and show all
            FILTERED_LOGS = [];
            renderLogsTable();
        };

        window.filterLogs = () => {
            const filterType = document.getElementById('logFilterType').value;
            let filteredLogs = [...SYSTEM_LOGS];
            
            if (filterType === 'single') {
                const singleDate = document.getElementById('logSingleDate').value;
                if (singleDate) {
                    filteredLogs = SYSTEM_LOGS.filter(log => {
                        const logDate = log.timestamp.split(' ')[0]; // Extract date part (YYYY-MM-DD)
                        return logDate === singleDate;
                    });
                }
            } else if (filterType === 'range') {
                const startDate = document.getElementById('logStartDate').value;
                const endDate = document.getElementById('logEndDate').value;
                
                if (startDate && endDate) {
                    filteredLogs = SYSTEM_LOGS.filter(log => {
                        const logDate = log.timestamp.split(' ')[0];
                        return logDate >= startDate && logDate <= endDate;
                    });
                }
            }
            
            FILTERED_LOGS = filteredLogs;
            renderFilteredLogsTable(filteredLogs);
        };

        function renderFilteredLogsTable(logs) {
            const tbody = document.getElementById('logsTableBody');
            tbody.innerHTML = '';
            
            if (logs.length === 0) {
               tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No logs found for the selected date(s).</td></tr>';
               return;
            }

            logs.forEach(log => {
                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 transition text-sm">
                        <td class="p-3 font-mono text-gray-500 text-xs text-center border-r border-gray-100">${log.id}</td>
                        <td class="p-3 font-semibold text-gray-700 border-r border-gray-100">${log.user_name}</td>
                         <td class="p-3 border-r border-gray-100">
                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs border border-blue-100 font-medium">${log.action}</span>
                        </td>
                        <td class="p-3 text-gray-600 border-r border-gray-100">${log.target}</td>
                        <td class="p-3 text-gray-500 text-xs">${log.timestamp}</td>
                    </tr>
                `;
            });
        }

        window.exportLogsToPDF = () => {
            const { jsPDF } = window.jspdf;
            
            // Check filter type to determine which logs to export
            const filterType = document.getElementById('logFilterType').value;
            let logsToExport;
            
            // If a filter is applied (not "all"), use FILTERED_LOGS
            if (filterType !== 'all') {
                logsToExport = FILTERED_LOGS;
                
                // Check if filter returned no results
                if (logsToExport.length === 0) {
                    alertMessage('No logs found for the selected date(s). Cannot export PDF.', 'error');
                    return;
                }
            } else {
                // Export all logs
                logsToExport = SYSTEM_LOGS;
                
                if (logsToExport.length === 0) {
                    alertMessage('No logs to export. Please ensure there are logs in the system.', 'error');
                    return;
                }
            }
            
            const doc = new jsPDF();

            // Get filter description for title
            let dateDescription = 'All Logs';
            
            if (filterType === 'single') {
                const singleDate = document.getElementById('logSingleDate').value;
                if (singleDate) dateDescription = `Logs for ${singleDate}`;
            } else if (filterType === 'range') {
                const startDate = document.getElementById('logStartDate').value;
                const endDate = document.getElementById('logEndDate').value;
                if (startDate && endDate) dateDescription = `Logs from ${startDate} to ${endDate}`;
            }

            // Title
            doc.setFontSize(18);
            doc.setTextColor(0, 77, 64); // Primary dark color
            doc.text('PDRIMS System Log Report', 14, 20);
            
            // Subtitle / Filter info
            doc.setFontSize(11);
            doc.setTextColor(100);
            doc.text(dateDescription, 14, 28);
            doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 34);
            
            // Prepare table data
            const tableData = logsToExport.map(log => [
                log.id,
                log.user_name,
                log.action,
                log.target.substring(0, 40) + (log.target.length > 40 ? '...' : ''),
                log.timestamp
            ]);

            // Generate table
            doc.autoTable({
                startY: 42,
                head: [['Log ID', 'User', 'Action', 'Target', 'Timestamp']],
                body: tableData,
                theme: 'striped',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { 
                    fillColor: [0, 77, 64], 
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                columnStyles: {
                    0: { cellWidth: 15 },
                    1: { cellWidth: 35 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 60 },
                    4: { cellWidth: 40 }
                }
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

            // Save the PDF
            const filename = `PDRIMS_System_Logs_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);
            
            alertMessage(`PDF exported successfully: ${filename}`, 'info');
        };

        function toggleCreateUserForm(show) {
            const formContainer = document.getElementById('createUserFormContainer');
            if(show) {
                formContainer.classList.remove('hidden');
                toggleRoleFields(); // Ensure correct state on open
            } else {
                formContainer.classList.add('hidden');
            }
        }

        function toggleRoleFields() {
            const role = document.getElementById('newUserRole').value;
            const isViewer = role === 'Beneficiary (Viewer)';
            
            const designationContainer = document.getElementById('designationFieldContainer');
            const ageContainer = document.getElementById('ageFieldContainer');
            const positionInput = document.getElementById('newUserPosition');
            const ageInput = document.getElementById('newUserAge');

            if (isViewer) {
                designationContainer.classList.add('hidden');
                positionInput.removeAttribute('required');
                
                ageContainer.classList.remove('hidden');
                ageInput.setAttribute('required', 'required');
            } else {
                designationContainer.classList.remove('hidden');
                positionInput.setAttribute('required', 'required');
                
                ageContainer.classList.add('hidden');
                ageInput.removeAttribute('required');
            }
        }

        function handleCreateUser(e) {
            e.preventDefault();
            const name = document.getElementById('newUserName').value.trim();
            const email = document.getElementById('newUserEmail').value.trim();
            const role = document.getElementById('newUserRole').value;
            const password = document.getElementById('newUserPassword').value;
            const contact = document.getElementById('newUserContact').value.trim();
            const age = document.getElementById('newUserAge').value;
            const position = document.getElementById('newUserPosition').value;

            // Simple name split
            const nameParts = name.split(' ');
            const firstName = nameParts[0];
            const surname = nameParts.slice(1).join(' ') || 'User';

            // Prepare payload based on role
            const isViewer = role === 'Beneficiary (Viewer)';
            
            const payload = {
                action: 'create',
                firstName: firstName,
                surname: surname,
                email: email,
                password: password,
                role: role // 'Beneficiary (Viewer)', 'System Administrator', or 'Barangay Official'
            };

            if (isViewer) {
                 payload.type = 'viewer';
                 payload.age = age;
                 payload.contact = contact;
                 // Viewers created by admin are auto-approved members without specific household head link initially
                 // unless we add those fields. For now, basic registration.
                 payload.isMember = 0; 
            } else {
                 payload.type = 'official';
                 // For officials, role is saved in the 'role' column
            }
            
            fetch('api_admin_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alertMessage(`Account created successfully for ${name}!`, "info");
                    fetchUsers(); // Refresh list
                    toggleCreateUserForm(false);
                    e.target.reset();
                    
                    // Add log entry
                    logAction("Created User", `User: ${name} (${role})`);
                } else {
                    alertMessage('Error creating account: ' + (data.error || 'Unknown error'), "error");
                }
            })
            .catch(err => {
                alertMessage('Connection error: ' + err.message, "error");
            });
        }

        function approveUser(id) {
            fetch('api_admin_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approve', id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alertMessage('User approved successfully.', "info");
                    fetchUsers();
                    logAction("Approved User", `User ID: ${id}`);
                } else {
                    alertMessage('Error approving user: ' + (data.error || 'Unknown error'), "error");
                }
            })
            .catch(err => alertMessage('Connection error: ' + err.message, "error"));
        }

        function closeMessageModal() {
            document.getElementById('messageViewerModal').classList.add('hidden');
        }

        // --- MODAL & NOTIFICATION FUNCTIONS ---

        const universalModal = document.getElementById('universalModal');
        const modalContentWrapper = document.getElementById('modalContentWrapper');
        const modalBody = document.getElementById('modalBody');

        window.showModal = (contentHTML, maxWidthClass = 'max-w-xl') => {
            const universalModal = document.getElementById('universalModal');
            const modalBody = document.getElementById('modalBody');
            const modalContentWrapper = document.getElementById('modalContentWrapper');
            
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
             const universalModal = document.getElementById('universalModal');
             const modalBody = document.getElementById('modalBody');

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
        
        function renderInbox() {
            const container = document.getElementById('inboxContainer');
            container.innerHTML = '';

            if (MOCK_INBOX.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No new concerns.</p>';
                return;
            }

            MOCK_INBOX.forEach(msg => {
                const readClass = msg.read ? 'bg-white opacity-75' : 'bg-white';
                const statusBadge = msg.read ? '<span class="text-xs text-gray-400 border border-gray-200 px-2 py-0.5 rounded">Read</span>' : '<span class="text-xs text-white bg-red-500 px-2 py-0.5 rounded font-bold">New</span>';
                
                const { text } = getCategoryColorStyle(msg.category);

                container.innerHTML += `
                    <div onclick="viewMessage(${msg.id})" class="p-4 rounded-lg shadow-sm border border-gray-200 mb-3 hover:shadow-md transition cursor-pointer ${readClass}">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wide ${text}">${msg.category}</span>
                                <span class="text-xs text-gray-400">• ${msg.date}</span>
                            </div>
                            ${statusBadge}
                        </div>
                        <h4 class="font-bold text-gray-800 mb-1">${msg.subject}</h4>
                        <p class="text-xs text-gray-500 mb-2">From: ${msg.sender}</p>
                        <p class="text-sm text-gray-700 line-clamp-2 bg-gray-50 p-2 rounded border border-gray-100">${msg.message}</p>
                        <span class="text-primary-light text-xs font-semibold mt-2 hover:underline flex items-center">
                            View Full Details
                        </span>
                    </div>
                `;
            });
        }

        function viewMessage(id) {
            const msg = MOCK_INBOX.find(m => m.id === id);
            if (msg) {
                document.getElementById('messageSubject').textContent = msg.subject;
                document.getElementById('messageSender').textContent = msg.sender;
                document.getElementById('messageDate').textContent = msg.date;
                document.getElementById('messageBody').textContent = msg.message;

                const { text } = getCategoryColorStyle(msg.category);
                
                const categoryBadge = document.getElementById('messageCategoryBadge');
                categoryBadge.textContent = msg.category;
                categoryBadge.className = `text-xs font-bold uppercase tracking-wide ${text}`;

                document.getElementById('messageViewerModal').classList.remove('hidden');
                
                if (!msg.read) {
                    msg.read = true;
                    renderInbox();
                }
            }
        }

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
 
                 const profiledEl = document.getElementById('stat-profiled-count');
                 if(profiledEl) profiledEl.textContent = data.totalProfiled || 0;
                 
                 const destroyedEl = document.getElementById('stat-destroyed-count');
                 if(destroyedEl) destroyedEl.textContent = data.totalDestroyed || 0;
                 
                 const priorityEl = document.getElementById('stat-priority-count');
                 if(priorityEl) priorityEl.textContent = data.totalPriority || 0;
                 
                 const recoveryEl = document.getElementById('stat-recovery-percent');
                 if(recoveryEl) recoveryEl.textContent = `${data.recoveryPercent || 0}%`;
 
                renderDamageDistribution(data.damageStats || {});
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
             
             if (rateElement) rateElement.textContent = `${fulfillmentRate}%`;
             if (rateTextElement) rateTextElement.textContent = `Needs Met (${fulfillmentRate}%)`;
             if (remainingTextElement) {
                 const remaining = 100 - fulfillmentRate;
                 remainingTextElement.textContent = `Remaining Need (${remaining}%)`;
             }
 
             const progressCircle = document.getElementById('aidFulfillmentCircle');
             if (progressCircle) {
                 const radius = 60;
                 const circumference = 2 * Math.PI * radius; 
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
 
         window.viewHighPriorityHouseholds = () => {
              const link = document.querySelector('[data-target="HouseholdProfiles"]');
              if(link) link.click();
         };

        // --- Data Initialization ---
        let HOUSEHOLDS_DATA = [];
        let AID_RECORDS_DATA = [];

        window.fetchHouseholds = async () => {
             const tableBody = document.getElementById('householdTableBody');
             if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">Loading data...</td></tr>';
             
             try {
                const res = await fetch('api_households.php');
                const data = await res.json();
                HOUSEHOLDS_DATA = data.map(h => ({
                    ...h,
                    headName: h.full_name || 'N/A',
                    familyMembers: h.members || [],
                    damageStatus: h.damage_status,
                    headAge: h.head_age,
                    contactNumber: h.contact_number,
                    initialNeeds: h.initial_needs,
                    id: String(h.id).padStart(3, '0')
                }));
                filterAndSearchHouseholdList();
                populateHouseholdDropdown(); 
                updateDashboardMetrics(); 
             } catch (err) {
                 console.error('Error loading households:', err);
                 if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-red-500">Error loading data.</td></tr>';
             }
        };

        window.fetchAidRecords = async () => {
            const tableBody = document.getElementById('aidRecordTableBody');
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">Loading aid distribution data...</td></tr>';
            
            try {
                const res = await fetch('api_aid_records.php');
                const data = await res.json();
                AID_RECORDS_DATA = data.map(r => ({
                    ...r,
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

        // --- DRAWER FUNCTIONS ---
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
           
            const setText = (id, text) => {
                const el = document.getElementById(id);
                if(el) el.textContent = text;
            };

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

        window.closeAidDetailsDrawer = () => {
            document.getElementById('aidDetailsDrawer').classList.remove('drawer-open');
        };

        window.openAidDetailsDrawer = (aidId) => {
            const aidRecord = AID_RECORDS_DATA.find(r => r.id == aidId);
            if (!aidRecord) return alert('Aid Record not found for ID: ' + aidId);

            const household = HOUSEHOLDS_DATA.find(h => h.id === aidRecord.aidRecipientId);
            const headName = household ? household.headName : 'Household Not Found';
            const purok = household ? household.purok : 'N/A';

            const setText = (id, text) => {
                const el = document.getElementById(id);
                if(el) el.textContent = text;
            };

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
            closeDetailsDrawer();
            lucide.createIcons();
        };

        // --- HOUSEHOLD MASTERLIST FUNCTIONS ---
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

        window.renderHouseholdList = (data) => {
            const tableBody = document.getElementById('householdTableBody');
            if (!tableBody) return;

            if (data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400">No matching household profiles found.</td></tr>`;
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

        window.viewHighPriorityHouseholds = () => {
            const link = document.querySelector('[data-target="HouseholdProfiles"]');
            if(link) link.click();
            
            // Reset filters logic
            const damageFilter = document.getElementById('masterlistDamageFilter');
            const searchInput = document.getElementById('masterlistSearch');
            const purokFilter = document.getElementById('masterlistPurokFilter');

            if (searchInput) searchInput.value = '';
            if (purokFilter) purokFilter.value = '';
            if (damageFilter) damageFilter.value = '';
            
            filterAndSearchHouseholdList();
            
            setTimeout(() => {
                const masterListHeader = document.querySelector('h2.text-2xl.font-semibold.mt-10.mb-4');
                if(masterListHeader) {
                    masterListHeader.scrollIntoView({ behavior: 'smooth' });
                }
            }, 100);
        };

        // --- AID RECORD FUNCTIONS ---
        window.filterAndSearchAidList = () => {
            const searchText = (document.getElementById('aidlistSearch')?.value || '').toLowerCase();
            const aidTypeFilter = document.getElementById('aidlistAidTypeFilter')?.value || '';
            const recipientIdFilter = document.getElementById('aidlistRecipientFilter')?.value || '';

            const filteredAidRecords = AID_RECORDS_DATA.filter(record => {
                const recipientName = record.recipientName || '';
                const surname = recipientName.split(',')[0].toLowerCase().trim();
                const matchesSearch = !searchText || surname.includes(searchText);
                const matchesAidType = !aidTypeFilter || record.aidType === aidTypeFilter;
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
                return `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-semibold text-gray-700">${index + 1}</td>
                        <td class="p-4 text-sm">${record.recipientName || 'Unknown'}</td>
                        <td class="p-4 text-sm">${record.aidType || 'N/A'}</td>
                        <td class="p-4 text-sm">${record.quantity || '1'}</td>
                        <td class="p-4 text-sm">${record.dateDistributed || 'N/A'}</td>
                        <td class="p-4 text-sm space-x-2 whitespace-nowrap">
                            <button class="text-aid-color hover:text-aid-color-dark transition" onclick="openAidDetailsDrawer('${record.id}')">View</button>
                            <button class="text-red-600 hover:text-red-800 transition font-medium" onclick="startEditAidRecord('${record.id}')">Edit</button>
                        </td>
                    </tr>
                `;
            }).join('');
            tableBody.innerHTML = rowsHTML;
        };

        const populateHouseholdDropdown = () => {
            const singleSelect = document.getElementById('aidRecipientId');
            const aidRecipientFilter = document.getElementById('aidlistRecipientFilter');
            const multiSelectCheckboxes = document.getElementById('multiSelectCheckboxes');
            
            if (!singleSelect || !aidRecipientFilter) return;

            singleSelect.innerHTML = '<option value="">Select Single Household Head</option>';
            aidRecipientFilter.innerHTML = '<option value="">All Recipients</option>';
            
            if (multiSelectCheckboxes) {
                 multiSelectCheckboxes.innerHTML = '<p class="text-xs text-gray-400 p-2">No households profiled yet.</p>';
            }
           
            if (HOUSEHOLDS_DATA.length === 0) return;

            // Clear checkboxes container if data exists
            if (multiSelectCheckboxes) multiSelectCheckboxes.innerHTML = '';

            HOUSEHOLDS_DATA.forEach(h => {
                const idShort = h.id.substring(0, 8);
                const name = h.headName || 'Unnamed Head';
                const display = `ID: ${idShort} - ${name}`;
                
                const option = document.createElement('option');
                option.value = h.id;
                option.textContent = display;
                singleSelect.appendChild(option.cloneNode(true));
                aidRecipientFilter.appendChild(option.cloneNode(true));

                // Populate Multi-Select Checkboxes
                if (multiSelectCheckboxes) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center p-2 hover:bg-gray-50 border-b border-gray-100 last:border-0';
                    div.innerHTML = `
                        <input type="checkbox" value="${h.id}" class="recipient-checkbox h-4 w-4 text-primary-dark border-gray-300 rounded focus:ring-primary-light mr-3 cursor-pointer">
                        <span class="text-sm text-gray-700 font-medium">${display}</span>
                    `;
                    // Make the whole row clickable
                    div.onclick = (e) => {
                        if (e.target.type !== 'checkbox') {
                            const cb = div.querySelector('input[type="checkbox"]');
                            cb.checked = !cb.checked;
                        }
                    };
                    multiSelectCheckboxes.appendChild(div);
                }
            });
        };

        // --- EDIT/DELETE/SAVE LOGIC ---
        const MEMBER_ROW_HTML = () => `
            <div class="col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Surname</label>
                    <input type="text" placeholder="Surname" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">First Name</label>
                    <input type="text" placeholder="First Name" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Middle Initial</label>
                    <input type="text" placeholder="M.I." class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Age</label>
                    <input type="number" placeholder="Age" class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Gender</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Relationship</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select</option>
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
                        <option value="">Select</option>
                        <option value="Employed">Employed</option>
                        <option value="Self-Employed">Self-Employed</option>
                        <option value="Unemployed">Unemployed</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Condition</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select</option>
                        <option value="Alive">Alive</option>
                        <option value="Injured">Injured</option>
                        <option value="Missing">Missing</option>
                        <option value="Deceased">Deceased</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 required">Residence Status</label>
                    <select class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light" required>
                        <option value="">Select</option>
                        <option value="Resident">Resident</option>
                        <option value="Transferred">Transferred</option>
                        <option value="Outside">Outside</option>
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
            newRow.innerHTML = MEMBER_ROW_HTML();
            container.appendChild(newRow);
            lucide.createIcons();
        };

        window.removeMember = (button) => {
            const row = button.closest('.member-row');
            if (row) row.remove();
        };

        window.saveProfile = async (event) => {
            event.preventDefault();
            
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

            document.querySelectorAll('.member-row').forEach(row => {
                const inputs = row.querySelectorAll('input, select');
                if (inputs.length >= 9) {
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
                }
            });

            try {
                const isEditing = !!window.editingHouseholdId;
                const method = isEditing ? 'PUT' : 'POST';
                if (isEditing) headData.id = window.editingHouseholdId;
                
                const response = await fetch('api_households.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(headData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertMessage(isEditing ? 'Household profile updated!' : 'Household profile saved!', 'info');
                    if (isEditing) window.cancelEditProfile();
                    else {
                        document.getElementById('householdProfileForm').reset();
                        document.querySelectorAll('.member-row').forEach(row => row.remove());
                    }
                    fetchHouseholds();
                } else {
                    alertMessage('Error: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                alertMessage('Connection error: ' + error.message, 'error');
            }
        };

        window.startEditProfile = (householdId) => {
            closeDetailsDrawer();
            const profileLink = document.querySelector('[data-target="HouseholdProfiles"]');
            if (profileLink) profileLink.click();
            document.getElementById('householdProfileForm').scrollIntoView();

            const household = HOUSEHOLDS_DATA.find(h => h.id === householdId);
            if (!household) return;

            window.editingHouseholdId = household.id;
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

            document.querySelectorAll('.member-row').forEach(row => row.remove());
            (household.familyMembers || []).forEach(member => {
                window.addMember();
                const rows = document.querySelectorAll('.member-row');
                const lastRow = rows[rows.length - 1];
                const inputs = lastRow.querySelectorAll('input, select');
                inputs[0].value = member.surname || '';
                inputs[1].value = member.first_name || '';
                inputs[2].value = member.middle_initial || '';
                inputs[3].value = member.age || '';
                inputs[4].value = member.gender || '';
                inputs[5].value = member.relationship || '';
                inputs[6].value = member.livelihood_status || '';
                inputs[7].value = member.condition_status || '';
                inputs[8].value = member.residence_status || '';
            });
            alertMessage(`Editing Household ID: ${householdId}`, 'info');
        };

        window.cancelEditProfile = () => {
            window.editingHouseholdId = null;
            document.getElementById('householdProfileForm').reset();
            document.querySelectorAll('.member-row').forEach(row => row.remove());
        };

        window.confirmDeleteHousehold = (householdId) => {
            if(confirm("Are you sure you want to delete this household? This action cannot be undone.")) {
                deleteHousehold(householdId);
            }
        };

        window.deleteHousehold = async (householdId) => {
            try {
                const response = await fetch('api_households.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: householdId })
                });
                const result = await response.json();
                if (result.success) {
                    closeDetailsDrawer();
                    fetchHouseholds();
                    fetchAidRecords();
                    alertMessage('Household deleted successfully.', 'info');
                } else {
                    alertMessage('Error deleting household.', 'error');
                }
            } catch (error) {
                alertMessage('Error connecting to server.', 'error');
            }
        };

        // --- AID RECORD SAVE/EDIT FUNCTIONS ---
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

            const aidData = {
                householdId: selectedIds.length === 1 ? selectedIds[0] : selectedIds,
                aidType: document.getElementById('aidType').value,
                quantity: document.getElementById('quantity').value,
                dateDistributed: document.getElementById('dateDistributed').value,
                distributedBy: document.getElementById('distributedBy').value,
                notes: document.getElementById('distributionNotes').value
            };

            try {
                const isEditing = !!window.editingAidRecordId;
                const method = isEditing ? 'PUT' : 'POST';
                
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
                    
                    window.editingAidRecordId = null;
                    
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
                    
                    fetchAidRecords();
                    updateDashboardMetrics();
                    
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

        window.startEditAidRecord = (aidId) => {
            closeAidDetailsDrawer();
            const aidLink = document.querySelector('[data-target="AidDistributionRecords"]');
            if (aidLink) aidLink.click();
            
            const aidRecord = AID_RECORDS_DATA.find(r => r.id == aidId);
            if (!aidRecord) return;

            window.editingAidRecordId = aidRecord.id;
            document.getElementById('aidRecipientId').value = aidRecord.aidRecipientId || '';
            document.getElementById('aidType').value = aidRecord.aidType || '';
            document.getElementById('quantity').value = aidRecord.quantity || '';
            document.getElementById('dateDistributed').value = aidRecord.dateDistributed || '';
            document.getElementById('distributedBy').value = aidRecord.distributedBy || '';
            document.getElementById('distributionNotes').value = aidRecord.distributionNotes || '';
            
            alertMessage(`Editing Aid Record ID: ${aidId}`, 'info');
        };

        window.confirmDeleteAidRecord = (aidId) => {
            if(confirm("Are you sure you want to delete this aid record? This action cannot be undone.")) {
                deleteAidRecord(aidId);
            }
        };

        window.deleteAidRecord = async (aidId) => {
            try {
                const response = await fetch('api_aid_records.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: aidId })
                });
                const result = await response.json();
                if (result.success) {
                    closeAidDetailsDrawer();
                    fetchAidRecords();
                    updateDashboardMetrics();
                    alertMessage('Aid record deleted successfully.', 'info');
                } else {
                    alertMessage('Error deleting aid record.', 'error');
                }
            } catch (error) {
                alertMessage('Error connecting to server.', 'error');
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

        document.addEventListener('DOMContentLoaded', () => {
            
            // Update Admin ID from storage
            const currentUser = getCurrentUser();
            const adminId = currentUser.id ? String(currentUser.id) : '001';
            const adminIdDisplay = document.getElementById('userIdDisplay');
            if(adminIdDisplay) {
                adminIdDisplay.innerHTML = `User ID: <strong class="text-white">admin_${adminId}</strong>`;
            }

            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = link.getAttribute('data-target');
                    if(target) switchContent(target, link);
                });
            });

            fetchUsers(); // Load users from API
            // renderUserTable(); // Called by fetchUsers
            fetchLogs(); // Load logs
            renderInbox();

            updateDashboardMetrics();
            fetchHouseholds();
            fetchAidRecords();

            const dashboardLink = document.querySelector('[data-target="Dashboard"]');
            if(dashboardLink) switchContent('Dashboard', dashboardLink);

            // const adminLink = document.querySelector('[data-target="AdminManagement"]');
            // if(adminLink) switchContent('AdminManagement', adminLink);

            const defaultTab = document.querySelector('.admin-tab[data-target="admin-users"]');
            if (defaultTab) {
                window.switchAdminTab('admin-users', defaultTab);
            }
            
            lucide.createIcons();
        });
    </script>
</head>
<body class="bg-gray-100 p-4">

    <button id="menuButton" onclick="toggleSidebar()" class="lg:hidden fixed bottom-4 left-4 z-50 p-4 bg-primary-dark text-white rounded-full shadow-xl transition duration-300 transform hover:scale-105 active:scale-95">
        Menu
    </button>
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="lg:hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-30 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <div class="flex w-full h-[calc(100vh-2rem)]">

        <aside id="sidebar" class="w-70 bg-gradient-to-b from-primary-dark to-primary-deep flex-shrink-0 h-full overflow-y-auto flex flex-col shadow-xl fixed top-0 left-0 z-40 transform -translate-x-full transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 lg:rounded-lg lg:h-full">
            <div class="p-6 pt-8 pb-4 border-b border-white/20">
                <h1 class="text-white text-2xl font-extrabold uppercase tracking-wide">PDRIMS</h1>
                <p class="text-white/70 text-sm mt-1">Barangay Recovery System</p>
            </div>

            <nav class="flex-grow mt-4 space-y-1">
                <a href="#" class="nav-link" data-target="Dashboard">
                    <i data-lucide="home" class="nav-link-icon"></i>
                    Dashboard
                </a>
                <a href="#" class="nav-link" data-target="HouseholdProfiles">
                    <i data-lucide="users-round" class="nav-link-icon"></i>
                    Household Profiles
                </a>
                <a href="#" class="nav-link" data-target="AidDistributionRecords">
                    <i data-lucide="hand-helping" class="nav-link-icon"></i>
                    Aid Distribution
                </a>
                
                <a href="#" class="nav-link active" data-target="AdminManagement">
                    <i data-lucide="shield-check" class="nav-link-icon"></i>
                    Admin Management
                </a>

                <a href="#" class="nav-link" data-target="ReportsExports">
                    <i data-lucide="file-text" class="nav-link-icon"></i>
                    Reports & Exports
                </a>
                

            </nav>

            <div class="p-6 text-xs text-white/70 border-t border-white/10 flex justify-between items-center group">
                <div>
                    <p id="userIdDisplay">User ID: <strong class="text-white">admin-001</strong></p>
                    <p class="mt-1 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-blue-400 mr-2"></span>
                        System Administrator
                    </p>
                </div>
                <button onclick="confirmLogout()" class="text-white/80 hover:text-red-500 hover:drop-shadow-[0_0_8px_rgba(239,68,68,1)] p-2 transition-all duration-200" title="Logout">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </button>
            </div>
        </aside>

        <div class="flex-grow w-full lg:ml-4 h-full overflow-hidden">
            <main id="main-content" class="h-full p-8 bg-gradient-to-br from-white to-gray-50 shadow-xl rounded-lg overflow-y-auto">
           
                <header class="mb-4 pb-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-extrabold text-primary-dark" id="mainTitle">Admin Management</h1>
                        <p class="text-gray-500 mt-1">Manage users, approvals, and citizen concerns.</p>
                    </div>
                    <div class="text-sm font-semibold text-gray-700 bg-blue-100 text-blue-800 px-4 py-2 rounded-md border border-blue-200 flex items-center shadow-sm">
                        Admin Mode: Active
                    </div>
                </header>

                <section id="Dashboard" class="content-section">
                   
                    <div class="bg-gray-50 p-8 rounded-lg border border-gray-200 mb-6 mt-6">
                        <h2 class="text-xl font-bold text-primary-dark mb-6">Key Metrics Overview</h2>
                       
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Affected Households Profiled</p>
                                <p class="text-4xl font-bold mt-2 rotate-1 text-red-600" id="stat-profiled-count">0</p>
                                <p class="text-xs text-gray-400 mt-1">Total unique records</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Totally Destroyed Homes (100% Loss)</p>
                                <p class="text-4xl font-bold mt-2 text-yellow-600" id="stat-destroyed-count">0</p>
                                <p class="text-xs text-gray-400 mt-1">Needs verification</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">Average Aid Distribution Rate</p>
                                <p class="text-4xl font-bold mt-2 text-green-600" id="stat-recovery-percent">0%</p>
                                <p class="text-xs text-gray-400 mt-1">Based on total aid records vs households</p>
                            </div>
                            <div class="stat-card bg-gradient-to-br from-white to-gray-50">
                                <p class="text-sm font-medium text-gray-600">High Priority Households (>= 75% Damage)</p>
                                <p class="text-4xl font-bold mt-2 text-orange-600" id="stat-priority-count">0</p>
                         
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

                <section id="AdminManagement" class="content-section hidden">
                    
                    <div class="flex relative z-10 -mb-px">
                        <div data-target="admin-users" class="admin-tab active-tab" onclick="switchAdminTab('admin-users', this)">
                            User Accounts
                        </div>
                        <div data-target="admin-logs" class="admin-tab inactive-tab" onclick="switchAdminTab('admin-logs', this)">
                            System Log
                        </div>
                        <div data-target="admin-inbox" class="admin-tab inactive-tab" onclick="switchAdminTab('admin-inbox', this)">
                            Inbox <span id="inboxNewBadge" class="ml-2 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full hidden">New</span>
                        </div>
                        <div class="flex-grow border-b border-gray-200"></div>
                    </div>
                    
                    <div class="bg-white p-8 rounded-b-lg rounded-tr-lg border border-gray-200 shadow-xl min-h-[500px]">
                        
                        <div id="admin-users" class="tab-content">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">Account Management</h2>
                                    <p class="text-xs text-gray-500">Manage system access for officials and residents.</p>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="scrollToPendingAccounts()" class="bg-aid-color text-white px-4 py-2 rounded-md shadow-md hover:bg-aid-color-dark transition flex items-center">
                                         Account Requests (<span id="pendingCountButton">0</span>)
                                    </button>
                                    <button onclick="toggleCreateUserForm(true)" class="bg-gradient-to-r from-primary-deep to-primary-light text-white px-4 py-2 rounded-md shadow-md hover:shadow-lg transition flex items-center">
                                         Create Account
                                    </button>
                                </div>
                            </div>

                            <div id="createUserFormContainer" class="hidden mb-6 bg-green-50 border border-primary-light/50 p-6 rounded-lg shadow-inner">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="font-bold text-gray-900" id="createAccountTitle">Create New Account</h3>
                                    <button onclick="toggleCreateUserForm(false)" class="text-gray-600 hover:text-gray-800">Close</button>
                                </div>
                                <form onsubmit="handleCreateUser(event)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Full Name</label>
                                        <input type="text" id="newUserName" required class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="e.g. Juan Dela Cruz">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Email Address (for Login)</label>
                                        <input type="email" id="newUserEmail" required class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="e.g. juan@barangay.gov.ph">
                                    </div>
                                    
                                    <div id="designationFieldContainer">
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Barangay Position/Designation <span class="text-red-500">*</span></label>
                                        <input type="text" id="newUserPosition" required class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="e.g. Kagawad (Disaster Committee)">
                                        <p class="text-[10px] text-gray-600 mt-1">Required for Barangay Official accounts.</p>
                                    </div>
                                    
                                    <div id="ageFieldContainer" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Age <span class="text-red-500">*</span></label>
                                        <input type="number" id="newUserAge" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="e.g., 34">
                                        <p class="text-[10px] text-gray-600 mt-1">Required for Resident accounts.</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Contact Number</label>
                                        <input type="tel" id="newUserContact" required class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="e.g. 09XXXXXXXXX" pattern="[0-9]*" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">System Role</label>
                                        <select id="newUserRole" onchange="toggleRoleFields()" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black">
                                            <option value="Barangay Official">Barangay Official (Encoder)</option>
                                            <option value="System Administrator">System Administrator</option>
                                            <option value="Beneficiary (Viewer)">Beneficiary (Viewer)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Temporary Password</label>
                                        <div class="relative">
                                            <input type="password" id="newUserPassword" required class="w-full p-2 pr-10 border border-gray-300 rounded focus:ring-2 focus:ring-primary-light outline-none text-black" placeholder="********">
                                            <button type="button" onclick="togglePasswordVisibility('newUserPassword', 'newUserPasswordIcon')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                                <i id="newUserPasswordIcon" data-lucide="eye" class="w-5 h-5"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="md:col-span-2 mt-2">
                                        <button type="submit" class="w-full bg-primary-dark text-white font-bold py-2 rounded hover:bg-primary-deep transition shadow-md">
                                            Finalize Account Creation
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="pendingAccountsSection" class="pending-section-collapsible overflow-hidden mb-8">
                                <h3 class="text-xl font-bold text-red-600 mb-3">
                                    🚨 Pending Accounts
                                </h3>
                                <p class="text-sm text-red-700 mb-3">Accounts in this section require **immediate review and approval** to gain system access.</p>
                                <div class="bg-red-50 rounded-lg shadow border border-red-200 overflow-hidden">
                                    <table class="min-w-full divide-y divide-red-200">
                                        <thead class="bg-red-100">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">User Profile</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Contact Info</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Role</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Status & Activity</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pendingTableBody" class="bg-white divide-y divide-gray-200">
                                            </tbody>
                                    </table>
                                </div>
                            </div>

                            <hr class="my-8 border-gray-200">

                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-primary-dark mb-3">
                                    Active Official & Administrator Accounts
                                </h3>
                                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Profile</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status & Activity</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="officialsTableBody" class="bg-white divide-y divide-gray-200">
                                            </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-aid-color-dark mb-3">
                                    Active Resident & Beneficiary Viewer Accounts
                                </h3>
                                <p class="text-xs text-gray-500 mb-3">These accounts have limited access, typically for viewing their own profiles and aid records.</p>
                                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Profile</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status & Activity</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="residentsTableBody" class="bg-white divide-y divide-gray-200">
                                            </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="admin-logs" class="tab-content hidden">
                            <div class="mb-6">
                                <!-- Header Row -->
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-800">System Log</h2>
                                        <p class="text-xs text-gray-500">Track data modifications and user activities for accountability.</p>
                                    </div>
                                    
                                    <button onclick="exportLogsToPDF()" class="bg-gradient-to-r from-report-color-dark to-report-color text-white px-4 py-2 rounded-md text-sm hover:opacity-90 transition shadow-md flex items-center">
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export PDF
                                    </button>
                                </div>
                                
                                <!-- Filter Row -->
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <label class="text-sm font-medium text-gray-700">Filter by:</label>
                                        
                                        <select id="logFilterType" onchange="toggleLogDateInputs()" class="p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-light bg-white min-w-[140px]">
                                            <option value="all">All Logs</option>
                                            <option value="single">Specific Date</option>
                                            <option value="range">Date Range</option>
                                        </select>
                                        
                                        <div id="singleDateContainer" class="hidden items-center gap-2">
                                            <label class="text-sm text-gray-600">Date:</label>
                                            <input type="date" id="logSingleDate" class="p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-light bg-white">
                                        </div>
                                        
                                        <div id="dateRangeContainer" class="hidden items-center gap-2">
                                            <label class="text-sm text-gray-600">From:</label>
                                            <input type="date" id="logStartDate" class="p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-light bg-white">
                                            <label class="text-sm text-gray-600">To:</label>
                                            <input type="date" id="logEndDate" class="p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-light bg-white">
                                        </div>
                                        
                                        <button onclick="filterLogs()" class="bg-primary-light text-white px-4 py-2 rounded-md text-sm hover:bg-primary-dark transition shadow-sm flex items-center">
                                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i> Apply Filter
                                        </button>
                                        
                                        <button onclick="resetLogFilter()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-300 transition">
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log ID</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Data</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logsTableBody" class="bg-white divide-y divide-gray-200">
                                        </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="admin-inbox" class="tab-content hidden">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">Inbox & Feedback</h2>
                                    <p class="text-xs text-gray-500">Reports submitted by registered beneficiaries.</p>
                                </div>
                                <div class="bg-gray-100 px-3 py-1 rounded-full text-xs text-gray-500">Sort by: Newest First</div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div class="lg:col-span-2 space-y-4" id="inboxContainer">
                                    </div>
                                
                                <div class="hidden lg:block bg-blue-50 p-6 rounded-lg border border-blue-100 h-fit">
                                    <h3 class="font-bold text-blue-900 mb-2">
                                        Inbox Policy
                                    </h3>
                                    <p class="text-sm text-blue-800 mb-4 text-justify">
                                        This module facilitates communication between residents and the LGU. 
                                        Prioritize <strong>"Unrecorded Damage"</strong> reports to ensure accurate aid allocation.
                                    </p>
                                    <div class="w-full h-px bg-blue-200 my-4"></div>
                                    <div class="space-y-3">
                                        <div class="flex items-start text-sm text-blue-800">
                                            <span><strong>Critical:</strong> Infrastructure damage reports require on-site validation within 24 hours.</span>
                                        </div>
                                        <div class="flex items-start text-sm text-blue-800">
                                            <span><strong>Inquiries:</strong> Respond to aid schedule questions to prevent misinformation.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </section>

                <section id="Dashboard" class="content-section hidden">
                    <div class="h-64 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                        <p class="text-gray-400 font-medium">Dashboard Content Hidden</p>
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

            </main>
        </div>
    </div>

    <div id="deleteConfirmationModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-sm transform transition-all">
            <div class="flex items-center text-mgm-dark mb-4">
                <h3 class="text-lg font-bold">Confirm Account Deletion</h3>
            </div>
            
            <p class="text-sm text-gray-700 mb-4">
                You are about to permanently delete the account for <strong id="deleteUserName" class="text-mgm-dark"></strong>. This action is irreversible and will be logged.
            </p>
            
            <p class="text-sm text-gray-800 font-semibold mb-2">
                To proceed, please enter your Password:
            </p>
            
            <input type="password" id="adminPasswordInput" placeholder="Enter your password" class="w-full p-2 mb-4 border border-gray-300 rounded focus:ring-2 focus:ring-mgm-dark outline-none text-black">
            
            <div class="flex justify-end space-x-3">
                <button onclick="cancelDelete()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button onclick="confirmDeleteUser()" class="px-4 py-2 bg-mgm-dark text-white rounded-lg font-medium hover:bg-mgm-deep transition flex items-center">
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>

    <div id="messageViewerModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all">
            
            <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-4">
                <div class="flex flex-col">
                    <span id="messageCategoryBadge" class="text-xs font-bold uppercase tracking-wide">
                        Category Placeholder
                    </span>
                    <h3 id="messageSubject" class="text-2xl font-extrabold text-gray-900 mt-2">Subject Placeholder</h3>
                </div>
                <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500 mb-0.5">Sender</p>
                    <p id="messageSender" class="font-medium text-gray-800 text-base">Sender Placeholder</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500 mb-0.5">Date Received</p>
                    <p id="messageDate" class="font-medium text-gray-800 text-base">Date Placeholder</p>
                </div>
            </div>

            <hr class="my-4 border-gray-200">
            
            <div id="messageBody" class="text-base text-gray-700 whitespace-pre-wrap leading-relaxed">
                Message Body Placeholder
            </div>
            
            <div class="flex justify-end pt-6">
                <button onclick="closeMessageModal()" class="px-6 py-2 bg-primary-dark text-white rounded-lg font-medium hover:bg-primary-deep transition shadow-md">
                    Close & Acknowledge
                </button>
            </div>
        </div>
    </div>
    <!-- Drawers -->
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
            <button id="drawerDeleteButton" onclick="confirmDeleteHousehold('mock-id')" class="w-full bg-red-500 text-white px-4 py-2 rounded-md font-semibold hover:bg-red-600 transition flex items-center justify-center shadow-md">
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
            <button id="aidDrawerDeleteButton" onclick="confirmDeleteAidRecord('aid-id')" class="w-full bg-red-500 text-white px-4 py-2 rounded-md font-semibold hover:bg-red-600 transition flex items-center justify-center shadow-md">
                <i data-lucide="trash-2" class="w-5 h-5 inline-block mr-2"></i> Delete Distribution Record
            </button>
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
    <script>
        // Admin Inbox Functionality
        async function fetchAdminInbox() {
            const container = document.getElementById('inboxContainer');
            if (!container) return;
            
            try {
                const res = await fetch('api_concerns.php?type=all');
                const data = await res.json();
                
                // Toggle "New" Badge based on pending count
                const badge = document.getElementById('inboxNewBadge');
                if (badge) {
                    const pendingCount = (data.concerns || []).filter(c => c.status === 'Pending').length;
                    if (pendingCount > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
                
                if (!data.success || !data.concerns || data.concerns.length === 0) {
                    // Only show empty message if container is empty to avoid flickering
                    if (container.innerHTML.trim() === '') container.innerHTML = '<p class="text-center text-gray-500 py-4">No messages found.</p>';
                    // If we have existing content and new fetch is empty, we might want to clear it or keep showing "No messages"
                    // But here, if data is empty, we show data empty.
                    if (data.concerns && data.concerns.length === 0) container.innerHTML = '<p class="text-center text-gray-500 py-4">No incoming messages.</p>';
                    return;
                }

                const html = data.concerns.map(msg => {
                    const isPending = msg.status === 'Pending';
                    // Match Viewer Styles: Pending = Gray, Acknowledged/Resolved = Blue
                    const statusColor = isPending ? 'bg-gray-500 text-white' : 'bg-blue-900 text-white';
                    
                    // Map Resolved -> Acknowledged for display consistency
                    const displayStatus = (msg.status === 'Resolved' || msg.status === 'Acknowledged') ? 'Acknowledged' : msg.status;
                    const sender = msg.sender_name || 'Unknown User';
                    
                    // Logic for the detail view
                    let actionArea = '';
                    if (isPending) {
                        actionArea = `
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Response / Action Taken</label>
                                <textarea id="response-${msg.id}" rows="3" class="w-full p-2 border border-gray-300 rounded-md text-sm mb-3 focus:ring-primary-light focus:border-primary-light" placeholder="Enter your response here..."></textarea>
                                <div class="flex justify-end">
                                    <button onclick="acknowledgeConcern('${msg.id}')" class="bg-blue-900 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-800 transition shadow-sm">
                                        Close & Acknowledge
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        actionArea = `
                            <div class="mt-4 pt-4 border-t border-gray-100 bg-gray-50 p-4 rounded-md">
                                <span class="text-xs font-bold text-gray-500 uppercase">Admin Response</span>
                                <p class="text-sm text-gray-800 mt-1">${msg.response || 'No response recorded.'}</p>
                            </div>
                        `;
                    }

                    // Check if this item is currently open to preserve state (simple heuristic)
                    const detailsEl = document.getElementById(`admin-msg-${msg.id}`);
                    const isHidden = detailsEl ? detailsEl.classList.contains('hidden') : true;
                    const hiddenClass = isHidden ? 'hidden' : '';

                    return `
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition mb-3">
                            <div class="p-4 cursor-pointer flex justify-between items-start" onclick="toggleAdminMessage('${msg.id}')">
                                <div>
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="font-bold text-gray-800 text-sm">${sender}</span>
                                        <span class="text-xs text-gray-400">• ${msg.created_at}</span>
                                        ${msg.purok ? `<span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full border border-gray-200">Purok ${msg.purok}</span>` : ''}
                                    </div>
                                    <h4 class="font-medium text-primary-dark">${msg.subject}</h4>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColor}">${displayStatus}</span>
                            </div>
                            
                            <div id="admin-msg-${msg.id}" class="${hiddenClass} px-4 pb-4">
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <span class="text-xs font-bold text-gray-500 uppercase">Message</span>
                                    <p class="text-sm text-gray-800 mt-1 whitespace-pre-wrap leading-relaxed">${msg.description}</p>
                                </div>
                                ${actionArea}
                            </div>
                        </div>
                    `;
                }).join('');
                
                // Diffing is complex without VDOM, so we replace content.
                // To prevent closing expanded items, we only update if content length changed significantly or simple status check?
                // Actually, the `map` uses `document.getElementById` to check current state.
                // But `document.getElementById` checks the DOM *before* we overwrite `container.innerHTML` with `html`.
                // So `isHidden` captures the state correctly.
                // Then `container.innerHTML = html` triggers re-render with the proper classes.
                container.innerHTML = html;
                
            } catch (err) {
                console.error('Error fetching inbox:', err);
            }
        }

        function toggleAdminMessage(id) {
            const el = document.getElementById(`admin-msg-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        async function acknowledgeConcern(id) {
            const responseInput = document.getElementById(`response-${id}`);
            const response = responseInput ? responseInput.value : 'Acknowledged.';
            
            if (!confirm('Are you sure you want to close and acknowledge this concern?')) return;

            try {
                const res = await fetch('api_concerns.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'acknowledge',
                        id: id,
                        response: response
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('Concern acknowledged successfully'); // Simple alert for admin
                    fetchAdminInbox(); // Refresh list immediately
                } else {
                    alert('Failed to update status');
                }
            } catch (err) {
                console.error(err);
                alert('Connection error');
            }
        }

        // Initialize Inbox Poll
        document.addEventListener('DOMContentLoaded', () => {
             // Poll every 10s for quicker updates during demo/testing
             fetchAdminInbox(); 
             setInterval(fetchAdminInbox, 10000); 
        });
    </script>
    </body>
</html>