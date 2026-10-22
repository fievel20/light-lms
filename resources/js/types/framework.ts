export type CompetencyType = 'binary' | 'scale';

export type ScaleLevel = {
    value: number;
    label: string;
};

export type Competency = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: CompetencyType;
    validation_threshold: number | null;
    levels: ScaleLevel[];
};

export type AssessedCompetency = Competency & {
    self: { value: number; created_at: string } | null;
    trainer: {
        value: number;
        comment: string | null;
        created_at: string;
    } | null;
    acquired: boolean;
};

export type Framework = {
    id: number;
    name: string;
    description: string | null;
    competencies_count?: number;
    promotions_count?: number;
};

export type FrameworkOption = {
    id: number;
    name: string;
};

export type TypeOption = {
    value: CompetencyType;
    label: string;
};
