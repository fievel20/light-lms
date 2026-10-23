/** A trainer validation shown in the learner's activity feed. */
export type ActivityItem = {
    code: string;
    competency: string;
    level: string;
    acquired: boolean;
    comment: string | null;
    promotion: string;
    at: string;
};
