export type BadgeInfo = {
    key: string;
    label: string;
    description: string;
};

export type BadgeProgress = BadgeInfo & {
    earned: boolean;
    earned_at: string | null;
};

export type ActivityItem =
    | {
          type: 'validation';
          code: string;
          competency: string;
          level: string;
          acquired: boolean;
          comment: string | null;
          promotion: string;
          at: string;
      }
    | {
          type: 'badge';
          badge: BadgeInfo;
          at: string;
      };
