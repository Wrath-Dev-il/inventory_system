@php
    $masterListActive = request()->routeIs('admin.products.*')
        || request()->routeIs('admin.suppliers.*')
        || request()->routeIs('admin.customers.*')
        || request()->routeIs('admin.product-configuration.*');
    $salesAgentActive = request()->routeIs('admin.sales-agents.*');
    $inventoryActive = request()->routeIs('admin.inventory.*');
    $salesActive = request()->routeIs('admin.sales-order.*');
    $systemSecurityActive = request()->routeIs('admin.system-security.*');
@endphp

<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation" data-sidebar>
    <div class="admin-sidebar__top">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand" aria-label="Admin dashboard">
            <span class="admin-sidebar__logo-wrap">
                <img src="{{ asset('images/login/logo.png') }}" alt="CONTROL A logo" class="admin-sidebar__logo">
            </span>
            <span class="admin-sidebar__brand-copy">
                <span class="admin-sidebar__name">CONTROL A</span>
                <span class="admin-sidebar__eyebrow">Admin Panel</span>
            </span>
        </a>

        <button type="button" class="admin-sidebar__close" data-sidebar-close aria-label="Close navigation">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="admin-sidebar__nav">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" data-tooltip="Dashboard">
            <span class="admin-sidebar__icon-box">
                <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="2"/>
                    <rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="2"/>
                    <rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="2"/>
                    <rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="2"/>
                </svg>
            </span>
            <span class="admin-sidebar__label">Dashboard</span>
        </a>

        <a href="{{ route('admin.sales-agents.index') }}" class="admin-sidebar__link {{ $salesAgentActive ? 'is-active' : '' }}" data-tooltip="Sales Agent List">
            <span class="admin-sidebar__icon-box">
                <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    <path d="M19 8v6M16 11h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-sidebar__label">Sales Agent List</span>
        </a>

        <div class="admin-sidebar__group {{ $systemSecurityActive ? 'is-active' : '' }}" data-sidebar-group>
            <button
                type="button"
                class="admin-sidebar__link admin-sidebar__dropdown {{ $systemSecurityActive ? 'is-active' : '' }}"
                data-sidebar-toggle
                data-tooltip="System Security"
                aria-expanded="false"
                aria-controls="admin-system-security-menu"
            >
                <span class="admin-sidebar__icon-box">
                    <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 2 3 7v6c0 5.25 3.83 10.15 9 11 5.17-.85 9-5.75 9-11V7l-9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="admin-sidebar__label">System Security</span>
                <svg class="admin-sidebar__chevron" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="admin-sidebar__submenu" id="admin-system-security-menu">
                <a href="{{ route('admin.system-security.user-management') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.system-security.user-management') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>User Management</span>
                </a>

                <a href="{{ route('admin.system-security.archive') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.system-security.archive') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20 7V4a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3M3 7v13a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V7H3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M8 11h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Archive</span>
                </a>

                <a href="{{ route('admin.system-security.audit-trail') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.system-security.audit-trail') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 16l4-4 4 4 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Audit Trail</span>
                </a>

                <a href="{{ route('admin.system-security.data-sync') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.system-security.data-sync') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 12a8 8 0 0 1 15.57-3M22 12a8 8 0 0 1-15.57 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M18 5v4h-4M6 19v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Data Sync</span>
                </a>
            </div>
        </div>

        <div class="admin-sidebar__group {{ $masterListActive ? 'is-active' : '' }}" data-sidebar-group>
            <button
                type="button"
                class="admin-sidebar__link admin-sidebar__dropdown {{ $masterListActive ? 'is-active' : '' }}"
                data-sidebar-toggle
                data-tooltip="Master List"
                aria-expanded="false"
                aria-controls="admin-master-list-menu"
            >
                <span class="admin-sidebar__icon-box">
                    <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h6v5H4zM14 4h6v5h-6zM4 15h6v5H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M10 6.5h2a2 2 0 0 1 2 2V17h-4M14 17h2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="admin-sidebar__label">Master List</span>
                <svg class="admin-sidebar__chevron" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="admin-sidebar__submenu" id="admin-master-list-menu">
                <a href="{{ route('admin.products.index') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M12 13v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Product List</span>
                </a>

                <a href="{{ route('admin.product-configuration.index') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.product-configuration.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M12 13v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Product Configuration</span>
                </a>

                <a href="{{ route('admin.suppliers.index') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.suppliers.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 17h4V5H2v12h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 8h4l4 4v5h-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="7.5" cy="17.5" r="2.5" stroke="currentColor" stroke-width="2"/>
                            <circle cx="16.5" cy="17.5" r="2.5" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </span>
                    <span>Supplier List</span>
                </a>

                <a href="{{ route('admin.customers.index') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.customers.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Customer List</span>
                </a>

            </div>
        </div>

        <div class="admin-sidebar__group {{ $inventoryActive ? 'is-active' : '' }}" data-sidebar-group>
            <button
                type="button"
                class="admin-sidebar__link admin-sidebar__dropdown {{ $inventoryActive ? 'is-active' : '' }}"
                data-sidebar-toggle
                data-tooltip="Inventory Management"
                aria-expanded="false"
                aria-controls="admin-inventory-menu"
            >
                <span class="admin-sidebar__icon-box">
                    <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M20 7V4a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3M3 7v13a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V7H3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M12 11v6M9 14h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="admin-sidebar__label">Inventory Management</span>
                <svg class="admin-sidebar__chevron" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="admin-sidebar__submenu" id="admin-inventory-menu">
                <a href="{{ route('admin.inventory.adjustment') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.inventory.adjustment') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 20V10M8 14l4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 4h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Inventory Adjustment</span>
                </a>
            </div>
        </div>

        <div class="admin-sidebar__group {{ $salesActive ? 'is-active' : '' }}" data-sidebar-group>
            <button
                type="button"
                class="admin-sidebar__link admin-sidebar__dropdown {{ $salesActive ? 'is-active' : '' }}"
                data-sidebar-toggle
                data-tooltip="Sales"
                aria-expanded="false"
                aria-controls="admin-sales-menu"
            >
                <span class="admin-sidebar__icon-box">
                    <svg class="admin-sidebar__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <rect x="9" y="3" width="6" height="4" rx="1.5" stroke="currentColor" stroke-width="2"/>
                        <path d="M9 14l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="admin-sidebar__label">Sales</span>
                <svg class="admin-sidebar__chevron" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="admin-sidebar__submenu" id="admin-sales-menu">
                <a href="{{ route('admin.sales-order.index') }}" class="admin-sidebar__sublink {{ request()->routeIs('admin.sales-order.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__subicon-box">
                        <svg class="admin-sidebar__subicon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <rect x="9" y="3" width="6" height="4" rx="1.5" stroke="currentColor" stroke-width="2"/>
                            <path d="M9 14l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Sales Order</span>
                </a>
            </div>
        </div>
    </nav>

</aside>

<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" data-sidebar-close></div>
