@php
    $admin = auth()->user();
    $adminName = $admin?->display_name ?: $admin?->User_ID ?: 'Admin';
    $pageTitle = $pageTitle ?? $title ?? 'Dashboard';
    $pageSubtitle = $pageSubtitle ?? null;
    $breadcrumbs = $breadcrumbs ?? [
        ['label' => 'Portal'],
        ['label' => $pageTitle, 'active' => true],
    ];
    $hasProfilePicture = filled($admin?->profile_picture);
@endphp

<header class="admin-navbar">
    <div class="admin-navbar__left">
        <button type="button" class="admin-menu-button" id="adminMenuButton" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="admin-datetime" aria-label="Current date and time">
            <svg class="admin-datetime__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="4" y="5" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M16 3v4M8 3v4M4 10h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="admin-datetime__date" data-admin-date>Loading date</span>
            <span class="admin-datetime__divider" aria-hidden="true"></span>
            <span class="admin-datetime__time" data-admin-time>--:--:-- --</span>
        </div>
    </div>

    <div class="admin-navbar__right">
        <button type="button" class="admin-notification" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <span class="admin-navbar__divider" aria-hidden="true"></span>

        <div class="admin-profile" data-admin-profile>
            <button
                type="button"
                class="admin-profile__button"
                data-admin-profile-toggle
                aria-expanded="false"
                aria-controls="admin-profile-menu"
            >
                <span class="admin-profile__avatar" aria-hidden="true">
                    @if ($hasProfilePicture)
                        <img src="{{ route('admin.profile.avatar') }}" alt="">
                    @else
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    @endif
                </span>
                <span class="admin-profile__identity">
                    <span class="admin-profile__name">{{ $adminName }}</span>
                    <span class="admin-profile__role">Admin</span>
                </span>
            </button>

            <div class="admin-profile__menu" id="admin-profile-menu" data-admin-profile-menu hidden>
                <a href="{{ route('admin.profile') }}" class="admin-profile__item">
                    <span class="admin-profile__item-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>Profile</span>
                </a>

                <a href="{{ route('admin.settings') }}" class="admin-profile__item">
                    <span class="admin-profile__item-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.05.05a2 2 0 1 1-2.83 2.83l-.05-.05A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1 .6V20a2 2 0 1 1-4 0v-.08a1.8 1.8 0 0 0-1-.52 1.8 1.8 0 0 0-1.98.36l-.05.05a2 2 0 1 1-2.83-2.83l.05-.05A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-.6-1H4a2 2 0 1 1 0-4h.08a1.8 1.8 0 0 0 .52-1 1.8 1.8 0 0 0-.36-1.98l-.05-.05a2 2 0 1 1 2.83-2.83l.05.05A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1-.6V4a2 2 0 1 1 4 0v.08a1.8 1.8 0 0 0 1 .52 1.8 1.8 0 0 0 1.98-.36l.05-.05a2 2 0 1 1 2.83 2.83l-.05.05A1.8 1.8 0 0 0 19.4 9c.2.35.41.68.6 1H20a2 2 0 1 1 0 4h-.08a1.8 1.8 0 0 0-.52 1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Settings</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="admin-profile__logout-form">
                    @csrf
                    <button type="submit" class="admin-profile__item admin-profile__item--logout">
                        <span class="admin-profile__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
    @foreach ($breadcrumbs as $breadcrumb)
        @if (! $loop->first)
            <span class="admin-breadcrumb__separator">/</span>
        @endif
        <span class="admin-breadcrumb__item {{ ($breadcrumb['active'] ?? false) ? 'is-active' : '' }}">
            {{ $breadcrumb['label'] }}
        </span>
    @endforeach
</nav>
