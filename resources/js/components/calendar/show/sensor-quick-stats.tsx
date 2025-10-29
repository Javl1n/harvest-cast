import { SensorReadingInterface } from "@/types";

interface SensorQuickStatsProps {
    readingsCount: number;
    schedulesCount: number;
    createdAt: string;
    latestReading: SensorReadingInterface | null;
}

export const SensorQuickStats = ({
    readingsCount,
    schedulesCount,
    createdAt,
    latestReading
}: SensorQuickStatsProps) => {
    const sensorAge = Math.floor(
        (new Date().getTime() - new Date(createdAt).getTime()) / (1000 * 60 * 60 * 24)
    );

    const lastUpdateHours = latestReading
        ? Math.floor(
            (new Date().getTime() - new Date(latestReading.created_at).getTime()) / (1000 * 60 * 60)
        )
        : null;

    return (
        <div className="bg-card border border-border rounded-lg p-4">
            <h3 className="text-sm font-semibold mb-3">Quick Stats</h3>
            <div className="grid grid-cols-2 gap-3 text-xs">
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Total Readings</span>
                    <span className="font-medium">{readingsCount}</span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Total Plantings</span>
                    <span className="font-medium">{schedulesCount}</span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Sensor Age</span>
                    <span className="font-medium">{sensorAge} days</span>
                </div>
                {lastUpdateHours !== null && (
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Last Update</span>
                        <span className="font-medium">{lastUpdateHours}h ago</span>
                    </div>
                )}
            </div>
        </div>
    );
};
