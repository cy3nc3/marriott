export type * from './auth';
export type * from './dashboard';
export type * from './navigation';
export type * from './ui';

import type { Auth } from './auth';

export type AnnouncementNotification = {
    id: number;
    title: string;
    content_preview: string;
    created_at: string | null;
    publish_at: string | null;
    expires_at: string | null;
    type: 'notice' | 'event';
    response_mode: 'none' | 'ack_rsvp';
    event_starts_at: string | null;
    event_ends_at: string | null;
    response_deadline_at: string | null;
    is_cancelled: boolean;
    cancelled_at: string | null;
    cancel_reason: string | null;
    viewer_response_status: 'none' | 'ack_only' | 'yes' | 'no' | 'maybe';
    requires_action: boolean;
    is_read: boolean;
};

export type NotificationPayload = {
    announcements: AnnouncementNotification[];
    unread_count: number;
};

export type SharedData = {
    name: string;
    auth: Auth;
    active_academic_year: {
        id: number;
        name: string;
        status: string;
    } | null;
    flash: {
        login_welcome_toast: {
            key: string;
            title: string;
            description: string;
        } | null;
    };
    notifications: NotificationPayload;
    ui: {
        is_handheld: boolean;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
};
