import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

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
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
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

export interface Connection {
    id: string;
    name: string;
    description: string;
    image: string;
    access?: boolean;
}

export interface UserConnection {
    user_id: int;
    metas: UserConnectionMeta[];
}

export interface UserConnectionMeta {
    key: string;
    value: any;
}

export interface UserInvestment {
    id: int;
    user_id: int;
    connection_id: int;
    ticker: string;
    name?: string;
    amount: float;
    currency: string;
    synced_at: string;
    average_price: float;
    current_price: float;
    current_value: float;
}