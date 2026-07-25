export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface PageProps extends Record<string, unknown> {
    auth: {
        user: AuthUser | null;
        roles: string[];
    };
    flash: {
        success?: string;
        error?: string;
    };
}
