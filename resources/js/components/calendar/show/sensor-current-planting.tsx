import { Link, useForm } from "@inertiajs/react";
import { Wheat, AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { CropSchedule, SensorInterface } from "@/types";
import crops from "@/routes/crops";
import { formatShortDate } from "./sensor-utils";

interface SensorCurrentPlantingProps {
    sensor: SensorInterface;
    hasPlantings: boolean;
    latestSchedule: CropSchedule | null;
    isCurrentPlantingHarvested: boolean;
    isAdmin: boolean;
}

export const SensorCurrentPlanting = ({
    sensor,
    hasPlantings,
    latestSchedule,
    isCurrentPlantingHarvested,
    isAdmin
}: SensorCurrentPlantingProps) => {
    const { patch, processing } = useForm();

    const handleHarvest = () => {
        if (!latestSchedule) {
            return;
        }

        patch(crops.harvest(latestSchedule.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                // The page will automatically re-render with updated data
            },
            onError: (errors) => {
                console.error('Failed to mark as harvested:', errors);
            }
        });
    };

    return (
        <div className="bg-card border border-border rounded-lg p-4">
            <div className="flex items-center gap-2 mb-3">
                <Wheat className="h-4 w-4" />
                <h3 className="text-sm font-semibold">Current Planting</h3>
            </div>

            {hasPlantings && latestSchedule && !isCurrentPlantingHarvested ? (
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <h4 className="font-semibold">{latestSchedule.commodity?.name || 'Unknown Crop'}</h4>
                                <Badge className="bg-blue-100 text-blue-800 text-xs px-1 py-0">
                                    Growing
                                </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Planted {formatShortDate(latestSchedule.date_planted)}
                            </p>
                        </div>
                        {isAdmin && (
                            <div className="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="text-xs h-7"
                                    onClick={handleHarvest}
                                    disabled={processing}
                                >
                                    {processing ? 'Harvesting...' : 'Mark Harvested'}
                                </Button>
                                <Button size="sm" className="text-xs h-7" disabled>
                                    Harvest First
                                </Button>
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <div className="text-muted-foreground">Area</div>
                            <div className="font-medium">{latestSchedule.hectares}ha</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Seeds</div>
                            <div className="font-medium">{latestSchedule.seeds_planted.toLocaleString()}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Expected Harvest</div>
                            <div className="font-medium">
                                {latestSchedule.expected_harvest_date
                                    ? formatShortDate(latestSchedule.expected_harvest_date)
                                    : 'Not set'
                                }
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Expected Income</div>
                            <div className="font-medium">
                                ${latestSchedule.expected_income?.toLocaleString() || '0'}
                            </div>
                        </div>
                    </div>

                    <div className="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                        <div className="flex items-center gap-1 text-yellow-800">
                            <AlertCircle className="h-3 w-3" />
                            <span className="font-medium">Crop is still growing</span>
                        </div>
                        <div className="text-yellow-700 mt-1">
                            Harvest this crop before planting a new one on this sensor.
                        </div>
                    </div>
                </div>
            ) : (
                <div className="text-center py-6">
                    <Wheat className="h-8 w-8 mx-auto mb-2 text-muted-foreground opacity-50" />
                    <p className="text-sm font-medium mb-1">No Active Planting</p>
                    <p className="text-xs text-muted-foreground mb-3">
                        {isCurrentPlantingHarvested && latestSchedule
                            ? `Sensor is available. Last harvest: ${latestSchedule.commodity?.name} on ${formatShortDate(latestSchedule.actual_harvest_date!)}`
                            : 'This sensor is ready for a new planting.'
                        }
                    </p>
                    {isAdmin && (
                        <Link href={crops.create(sensor)}>
                            <Button size="sm" className="text-xs">
                                <Wheat className="h-3 w-3 mr-1" />
                                Plant Crop
                            </Button>
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
};
