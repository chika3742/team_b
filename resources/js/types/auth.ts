export type UserRole = 'user' | 'staff';

export type User = {
    id: number;
    user_card_id: string;
    name: string;
    email: string | null;
    avatar?: string;
    role: UserRole;
    must_change_password: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
