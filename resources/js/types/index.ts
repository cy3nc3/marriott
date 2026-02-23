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
    expires_at: string | null;
    is_read: boolean;
};

export type NotificationPayload = {
    announcements: AnnouncementNotification[];
    unread_count: number;
};

export type SharedData = {
    name: string;
    auth: Auth;
    notifications: NotificationPayload;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
