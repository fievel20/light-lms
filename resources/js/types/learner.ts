export type Learner = {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    invited_at: string | null;
    activated_at: string | null;
};

export type ImportReport = {
    created: number;
    enrolled: number;
    errors: { line: number; message: string }[];
};
