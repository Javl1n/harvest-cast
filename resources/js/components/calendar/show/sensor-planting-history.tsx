import { CropSchedule } from "@/types";
import { formatShortDate } from "./sensor-utils";

interface SensorPlantingHistoryProps {
    schedules: CropSchedule[];
}

export const SensorPlantingHistory = ({ schedules }: SensorPlantingHistoryProps) => {
    if (!schedules || schedules.length === 0) {
        return null;
    }

    return (
        <div className="bg-card border border-border rounded-lg p-4">
            <h3 className="text-sm font-semibold mb-3">Planting History</h3>
            <div className="space-y-2 max-h-40 overflow-y-auto">
                {schedules.map((schedule) => (
                    <div key={schedule.id} className="p-2 bg-muted/30 rounded">
                        <div className="font-medium text-sm">{schedule.commodity?.name || 'Unknown'}</div>
                        <div className="text-xs text-muted-foreground">
                            Planted {formatShortDate(schedule.date_planted)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {schedule.hectares}ha • {schedule.seeds_planted.toLocaleString()} seeds
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};
