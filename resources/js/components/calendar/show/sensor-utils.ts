export const formatShortDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

export const getMoistureColor = (moisture: number): string => {
    return `hsl(${(moisture / 100) * 120}, 100%, 50%)`;
};

export const getMoistureStatus = (moisture: number): { status: string; color: string } => {
    if (moisture >= 70) {
        return { status: 'High', color: 'bg-green-500' };
    }
    if (moisture >= 40) {
        return { status: 'Medium', color: 'bg-yellow-500' };
    }
    return { status: 'Low', color: 'bg-red-500' };
};
