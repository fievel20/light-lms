export type Sequence = {
    id: number;
    title: string;
    summary: string | null;
    content?: string;
    html?: string;
    updated_at?: string;
    learning_paths?: string[];
};
