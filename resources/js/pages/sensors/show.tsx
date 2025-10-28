import { useSetPanelSize } from "@/hooks/use-set-panel-size";
import AppLayout from "@/layouts/app-layout";
import crops from "@/routes/crops";
import calendar from "@/routes/calendar";
import { BreadcrumbItem, SensorInterface, CropCareRecommendation, CurrentConditions, YieldForecast } from "@/types";
import { Head, Link, usePage, useForm } from "@inertiajs/react";
import CropCareRecommendations from "@/components/crop-care-recommendations";
import YieldForecastCard from "@/components/yield-forecast-card";
import {
    CircleDot,
    MapPin,
    Droplets,
    ArrowLeft,
    Clock,
    Wheat,
    AlertCircle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sensor Calendar',
        href: calendar.index().url,
    },
];

const SensorsShow = () => {
     const {sensor, careRecommendations, currentConditions, hasCareRecommendations, yieldForecast} = usePage<{
         sensor: SensorInterface;
         careRecommendations: CropCareRecommendation[];
         currentConditions: CurrentConditions | null;
         hasCareRecommendations: boolean;
         yieldForecast: YieldForecast | null;
     }>().props;
     useSetPanelSize(30);

     const { patch, processing } = useForm();

     const handleHarvest = () => {
          if (!latestSchedule) return;

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

     const formatShortDate = (dateString: string) => {
          return new Date(dateString).toLocaleDateString('en-US', {
               month: 'short',
               day: 'numeric'
          });
     };

     const getMoistureColor = (moisture: number) => {
          return `hsl(${(moisture / 100) * 120}, 100%, 50%)`;
     };

     const getMoistureStatus = (moisture: number) => {
          if (moisture >= 70) return { status: 'High', color: 'bg-green-500' };
          if (moisture >= 40) return { status: 'Medium', color: 'bg-yellow-500' };
          return { status: 'Low', color: 'bg-red-500' };
     };

     const hasPlantings = sensor.schedules && sensor.schedules.length > 0;
     const latestSchedule = sensor.latest_schedule;
     const isCurrentPlantingHarvested = latestSchedule?.actual_harvest_date ? true : false;
     const canPlantNew = !hasPlantings || isCurrentPlantingHarvested;


     return (
          <>
               <Head title={`Sensor ${sensor.id.substring(0, 8)}`} />
               <div className="p-4 space-y-4">
                    {/* Header */}
                    <div className="flex items-center gap-3 mb-4">
                         <Link href={calendar.index()}>
                              <Button variant="ghost" size="sm" className="p-1">
                                   <ArrowLeft className="h-4 w-4" />
                              </Button>
                         </Link>
                         <div>
                              <h1 className="text-xl font-bold">Sensor Details</h1>
                              <p className="text-xs text-muted-foreground">ID: {sensor.id.substring(0, 23)}...</p>
                         </div>
                    </div>

                    {/* Current Status */}
                    <div className="bg-card border border-border rounded-lg p-4 space-y-3">
                         <div className="flex items-center gap-2 mb-3">
                              <CircleDot
                                   className="h-4 w-4"
                                   style={{
                                        color: sensor.latest_reading
                                             ? getMoistureColor(sensor.latest_reading.moisture)
                                             : '#6b7280'
                                   }}
                              />
                              <h3 className="text-sm font-semibold">Current Status</h3>
                         </div>

                         {sensor.latest_reading ? (
                              <div className="grid grid-cols-3 gap-3">
                                   <div className="text-center">
                                        <Droplets className="h-6 w-6 mx-auto mb-1 text-blue-500" />
                                        <div className="text-2xl font-bold">{sensor.latest_reading.moisture}%</div>
                                        <div className="text-xs text-muted-foreground mb-1">Soil Moisture</div>
                                        <Badge className={`text-xs px-1 py-0 ${getMoistureStatus(sensor.latest_reading.moisture).color} text-white`}>
                                             {getMoistureStatus(sensor.latest_reading.moisture).status}
                                        </Badge>
                                   </div>
                                   <div className="text-center">
                                        <MapPin className="h-6 w-6 mx-auto mb-1 text-green-500" />
                                        <div className="text-xs font-medium">
                                             {sensor.latest_reading.latitude.toFixed(2)},<br/>
                                             {sensor.latest_reading.longitude.toFixed(2)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">GPS Coordinates</div>
                                   </div>
                                   <div className="text-center">
                                        <Clock className="h-6 w-6 mx-auto mb-1 text-purple-500" />
                                        <div className="text-xs font-medium">
                                             {formatShortDate(sensor.latest_reading.created_at)}
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

                    {/* Quick Stats */}
                    <div className="bg-card border border-border rounded-lg p-4">
                         <h3 className="text-sm font-semibold mb-3">Quick Stats</h3>
                         <div className="grid grid-cols-2 gap-3 text-xs">
                              <div className="flex justify-between">
                                   <span className="text-muted-foreground">Total Readings</span>
                                   <span className="font-medium">{sensor.readings?.length || 0}</span>
                              </div>
                              <div className="flex justify-between">
                                   <span className="text-muted-foreground">Active Plantings</span>
                                   <span className="font-medium">{sensor.schedules?.length || 0}</span>
                              </div>
                              <div className="flex justify-between">
                                   <span className="text-muted-foreground">Sensor Age</span>
                                   <span className="font-medium">
                                        {Math.floor((new Date().getTime() - new Date(sensor.created_at).getTime()) / (1000 * 60 * 60 * 24))} days
                                   </span>
                              </div>
                              {sensor.latest_reading && (
                                   <div className="flex justify-between">
                                        <span className="text-muted-foreground">Last Update</span>
                                        <span className="font-medium">
                                             {Math.floor((new Date().getTime() - new Date(sensor.latest_reading.created_at).getTime()) / (1000 * 60 * 60))}h ago
                                        </span>
                                   </div>
                              )}
                         </div>
                    </div>

                    {/* Current Planting */}
                    <div className="bg-card border border-border rounded-lg p-4">
                         <div className="flex items-center gap-2 mb-3">
                              <Wheat className="h-4 w-4" />
                              <h3 className="text-sm font-semibold">Current Planting</h3>
                         </div>

                         {hasPlantings && latestSchedule ? (
                              <div className="space-y-3">
                                   <div className="flex items-center justify-between">
                                        <div>
                                             <div className="flex items-center gap-2">
                                                  <h4 className="font-semibold">{latestSchedule.commodity?.name || 'Unknown Crop'}</h4>
                                                  {isCurrentPlantingHarvested ? (
                                                       <Badge className="bg-green-100 text-green-800 text-xs px-1 py-0">
                                                            Harvested
                                                       </Badge>
                                                  ) : (
                                                       <Badge className="bg-blue-100 text-blue-800 text-xs px-1 py-0">
                                                            Growing
                                                       </Badge>
                                                  )}
                                             </div>
                                             <p className="text-xs text-muted-foreground">
                                                  Planted {formatShortDate(latestSchedule.date_planted)}
                                                  {isCurrentPlantingHarvested && latestSchedule.actual_harvest_date && (
                                                       <span> • Harvested {formatShortDate(latestSchedule.actual_harvest_date)}</span>
                                                  )}
                                             </p>
                                        </div>
                                        <div className="flex gap-2">
                                             {!isCurrentPlantingHarvested && (
                                                  <Button
                                                       size="sm"
                                                       variant="outline"
                                                       className="text-xs h-7"
                                                       onClick={handleHarvest}
                                                       disabled={processing}
                                                  >
                                                       {processing ? 'Harvesting...' : 'Mark Harvested'}
                                                  </Button>
                                             )}
                                             {canPlantNew ? (
                                                  <Link href={crops.create(sensor)}>
                                                       <Button size="sm" className="text-xs h-7">
                                                            {isCurrentPlantingHarvested ? 'Plant New' : 'Manage'}
                                                       </Button>
                                                  </Link>
                                             ) : (
                                                  <Button size="sm" className="text-xs h-7" disabled>
                                                       Harvest First
                                                  </Button>
                                             )}
                                        </div>
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
                                             <div className="text-muted-foreground">{isCurrentPlantingHarvested ? 'Harvested' : 'Expected Harvest'}</div>
                                             <div className="font-medium">
                                                  {isCurrentPlantingHarvested && latestSchedule.actual_harvest_date
                                                       ? formatShortDate(latestSchedule.actual_harvest_date)
                                                       : latestSchedule.expected_harvest_date
                                                       ? formatShortDate(latestSchedule.expected_harvest_date)
                                                       : 'Not set'
                                                  }
                                             </div>
                                        </div>
                                        <div>
                                             <div className="text-muted-foreground">{isCurrentPlantingHarvested ? 'Actual Income' : 'Expected Income'}</div>
                                             <div className="font-medium">
                                                  ${(isCurrentPlantingHarvested ? latestSchedule.income : latestSchedule.expected_income)?.toLocaleString() || '0'}
                                             </div>
                                        </div>
                                   </div>

                                   {!isCurrentPlantingHarvested && (
                                        <div className="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                                             <div className="flex items-center gap-1 text-yellow-800">
                                                  <AlertCircle className="h-3 w-3" />
                                                  <span className="font-medium">Crop is still growing</span>
                                             </div>
                                             <div className="text-yellow-700 mt-1">
                                                  Harvest this crop before planting a new one on this sensor.
                                             </div>
                                        </div>
                                   )}
                              </div>
                         ) : (
                              <div className="text-center py-6">
                                   <Wheat className="h-8 w-8 mx-auto mb-2 text-muted-foreground opacity-50" />
                                   <p className="text-sm font-medium mb-1">No Crops Planted</p>
                                   <p className="text-xs text-muted-foreground mb-3">This sensor doesn't have any active plantings.</p>
                                   {canPlantNew ? (
                                        <Link href={crops.create(sensor)}>
                                             <Button size="sm" className="text-xs">
                                                  <Wheat className="h-3 w-3 mr-1" />
                                                  Plant Crop
                                             </Button>
                                        </Link>
                                   ) : (
                                        <Button size="sm" className="text-xs" disabled>
                                             <Wheat className="h-3 w-3 mr-1" />
                                             Harvest Required
                                        </Button>
                                   )}
                              </div>
                         )}
                    </div>

                    {/* Yield Forecast */}
                    {yieldForecast && !isCurrentPlantingHarvested && (
                         <YieldForecastCard
                              forecast={yieldForecast}
                              cropName={latestSchedule?.commodity?.name}
                         />
                    )}

                    {/* Crop Care Recommendations */}
                    {hasCareRecommendations && (
                         <CropCareRecommendations
                              recommendations={careRecommendations}
                              currentConditions={currentConditions}
                              cropName={latestSchedule?.commodity?.name}
                              daysSincePlanting={latestSchedule?.date_planted
                                   ? Math.floor((new Date().getTime() - new Date(latestSchedule.date_planted).getTime()) / (1000 * 60 * 60 * 24))
                                   : undefined
                              }
                         />
                    )}

                    {/* Planting History */}
                    {sensor.schedules && sensor.schedules.length > 0 && (
                         <div className="bg-card border border-border rounded-lg p-4">
                              <h3 className="text-sm font-semibold mb-3">Planting History</h3>
                              <div className="space-y-2 max-h-40 overflow-y-auto">
                                   {sensor.schedules.map((schedule) => (
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
                    )}
               </div>
          </>
     )
};

SensorsShow.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default SensorsShow;
