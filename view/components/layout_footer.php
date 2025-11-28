            </div>
        </div>

        <!-- jQuery (required for DataTables) -->
        <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
        <!-- DataTables JavaScript -->
        <script src="../../node_modules/datatables.net/js/dataTables.min.js"></script>
        <!-- Notification System -->
        <script src="../js/notifications.js"></script>
        <!-- Include Sidebar JavaScript -->
        <script src="../components/sidebar.js"></script>
        <!-- Dark mode toggle script (persists preference) -->
        <script>
            (function(){
                const btn = document.getElementById('dark-mode-toggle');
                const icon = document.getElementById('dark-mode-icon');
                const storageKey = 'qcu_ams_dark_mode';

                function applyDarkMode(enabled){
                    if(enabled){
                        document.body.classList.add('dark-mode');
                        if(btn) btn.classList.add('active');
                        if(icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
                    } else {
                        document.body.classList.remove('dark-mode');
                        if(btn) btn.classList.remove('active');
                        if(icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); }
                    }
                }

                // Initialize from storage
                try {
                    const saved = localStorage.getItem(storageKey);
                    const enabled = saved === '1';
                    // Read role from body; if we're on Student pages, do not auto-apply dark mode.
                    const role = document.body ? document.body.dataset.role : null;
                    if (role && role === 'Student') {
                        // Intentionally do not auto-apply dark mode on Student landing pages.
                        // Dark mode will only be activated there via the Ctrl+M,E,L sequence.
                    } else {
                        applyDarkMode(enabled);
                    }
                } catch(e) { /* ignore storage errors */ }

                if(btn){
                    btn.addEventListener('click', function(){
                        const enabled = document.body.classList.toggle('dark-mode');
                        // update icon and button state
                        applyDarkMode(enabled);
                        try {
                            const role = document.body ? document.body.dataset.role : null;
                            // Do not persist preference when toggled from Student/Faculty pages
                            if (role !== 'Student') {
                                localStorage.setItem(storageKey, enabled ? '1' : '0');
                            }
                        } catch(e) {}
                    });
                }
                // Keyboard shortcut: Ctrl + ` (backquote) to toggle dark mode
                (function(){
                    window.addEventListener('keydown', function(e){
                        // ignore when typing in inputs/textareas/selects or contenteditable
                        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : null;
                        const editable = e.target && (e.target.isContentEditable || tag === 'input' || tag === 'textarea' || tag === 'select');
                        if (editable) return;

                        // Check ctrl + backquote (the key may be '`' or use the Backquote code)
                        const isBackquote = (e.key === '`' || e.code === 'Backquote');
                        if (e.ctrlKey && isBackquote) {
                            const currently = document.body.classList.contains('dark-mode');
                            const enabled = !currently;
                            applyDarkMode(enabled);
                            try {
                                const role = document.body ? document.body.dataset.role : null;
                                // Do not persist preference when toggled from Student/Faculty pages
                                if (role !== 'Student') {
                                    localStorage.setItem(storageKey, enabled ? '1' : '0');
                                }
                            } catch(e) {}
                        }
                    });
                })();
            })();
        </script>
    </body>
    </html>