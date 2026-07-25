export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface PageProps {
    auth: {
        user: AuthUser | null;
        roles: string[];
    };
    flash: {
        success?: string;
        error?: string;
    };
}
