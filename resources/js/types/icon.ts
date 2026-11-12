export type Icon = {
    id: number;
    name: string;
    url: string;
    // One colour: drawn in the colour of the text.
    monochrome: boolean;
    markdown: string;
    // Titles of the sequences that show it (library page only).
    sequences?: string[];
};
