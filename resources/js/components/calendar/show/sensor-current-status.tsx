import { CircleDot, Droplets, MapPin, Clock, AlertCircle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { SensorReadingInterface } from "@/types";
import { getMoistureColor, getMoistureStatus, formatShortDate } from "./sensor-utils";

interface SensorCurrentStatusProps {
    latestReading: SensorReadingInterface | null;
}

export const SensorCurrentStatus = ({ latestReading }: SensorCurrentStatusProps) => {
    return (
        <div className="bg-card border border-border rounded-lg p-4 space-y-3">
            <div className="flex items-center gap-2 mb-3">
                <CircleDot
                    className="h-4 w-4"
                    style={{
                        color: latestReading
                            ? getMoistureColor(latestReading.moisture)
                            : '#6b7280'
                    }}
                />
                <h3 className="text-sm font-semibold">Current Status</h3>
            </div>

            {latestReading ? (
                <div className="grid grid-cols-3 gap-3">
                    <div className="text-center">
                        <Droplets className="h-6 w-6 mx-auto mb-1 text-blue-500" />
                        <div className="text-2xl font-bold">{latestReading.moisture}%</div>
                        <div className="text-xs text-muted-foreground mb-1">Soil Moisture</div>
                        <Badge className={`text-xs px-1 py-0 ${getMoistureStatus(latestReading.moisture).color} text-white`}>
                            {getMoistureStatus(latestReading.moisture).status}
                        </Badge>
                    </div>
                    <div className="text-center">
                        <MapPin className="h-6 w-6 mx-auto mb-1 text-green-500" />
                        <div className="text-xs font-medium">
                            {latestReading.latitude.toFixed(2)},<br/>
                            {latestReading.longitude.toFixed(2)}
                        </div>
                        <div className="text-xs text-muted-foreground">GPS Coordinates</div>
                    </div>
                    <div className="text-center">
                        <Clock className="h-6 w-6 mx-auto mb-1 text-purple-500" />
                        <div className="text-xs font-medium">
                            {formatShortDate(latestReading.created_at)}
                        </div>
                        <div className="text-xs text-muted-foreground">Last Reading</div>
                    </div>
                </div>
            ) : (
                <div className="text-center py-4">
                    <AlertCircle className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
                    <p className="text-xs text-muted-foreground">No sensor readings available</p>
                </div>
            )}
        </div>
    );
};
