import { Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import calendar from "@/routes/calendar";

interface SensorHeaderProps {
    sensorId: string;
}

export const SensorHeader = ({ sensorId }: SensorHeaderProps) => {
    return (
        <div className="flex items-center gap-3 mb-4">
            <Link href={calendar.index()}>
                <Button variant="ghost" size="sm" className="p-1">
                    <ArrowLeft className="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 className="text-xl font-bold">Sensor Details</h1>
                <p className="text-xs text-muted-foreground">ID: {sensorId.substring(0, 23)}...</p>
            </div>
        </div>
    );
};
