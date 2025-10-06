import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    sensors: SensorInterface[];
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface SensorInterface {
    id: string;
    mac: string;
    created_at: string;
    updated_at: string;
    readings: SensorReadingInterface[];
    latest_reading: SensorReadingInterface;
    oldest_reading: SensorReadingInterface;
    [key: string]: unknown;
}

export interface SensorReadingInterface {
    id: string | number;
    sensor: SensorInterface;
    longitude: number;
    latitude: number;
    moisture: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}