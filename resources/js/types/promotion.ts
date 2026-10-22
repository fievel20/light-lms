export type Promotion = {
    id: number;
    name: string;
    framework_id?: number | null;
    framework?: { id: number; name: string } | null;
    starts_on: string;
    ends_on: string;
    archived_at: string | null;
};
