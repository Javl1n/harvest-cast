import { useSetPanelSize } from "@/hooks/use-set-panel-size";
import { useFlyToLocation } from "@/hooks/useFlyToLocation";
import AppLayout from "@/layouts/app-layout";
import calendar from "@/routes/calendar";
import { BreadcrumbItem, SensorInterface, CropCareRecommendation, CurrentConditions, YieldForecast } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import CropCareRecommendations from "@/components/crop-care-recommendations";
import YieldForecastCard from "@/components/yield-forecast-card";
import { SensorHeader } from "@/components/calendar/show/sensor-header";
import { SensorCurrentStatus } from "@/components/calendar/show/sensor-current-status";
import { SensorQuickStats } from "@/components/calendar/show/sensor-quick-stats";
import { SensorCurrentPlanting } from "@/components/calendar/show/sensor-current-planting";
import { SensorPlantingHistory } from "@/components/calendar/show/sensor-planting-history";
import { useSetAtom } from "jotai";
import { filterToSensorAtom } from "@/atoms/sensors-atom";
import { useEffect } from "react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sensor Calendar',
        href: calendar.index().url,
    },
];

const SensorsShow = () => {
     const {sensor, careRecommendations, currentConditions, hasCareRecommendations, yieldForecast, auth} = usePage<{
         sensor: SensorInterface;
         careRecommendations: CropCareRecommendation[];
         currentConditions: CurrentConditions | null;
         hasCareRecommendations: boolean;
         yieldForecast: YieldForecast | null;
         auth: { user: { id: number; name: string; email: string; role: 'admin' | 'farmer' } | null };
     }>().props;
     useSetPanelSize(30);
     const filterToSensor = useSetAtom(filterToSensorAtom);

     // Filter to show only this sensor on the map
     useEffect(() => {
          filterToSensor(sensor.id);

          // Cleanup: Show all sensors when leaving this page
          return () => {
               filterToSensor(null);
          };
     }, [sensor.id, filterToSensor]);

     useFlyToLocation(
          sensor.latest_reading?.latitude,
          sensor.latest_reading?.longitude,
          {
               zoom: 18,
               duration: 1500,
               offset: [window.innerWidth * 0.15, 0]
          }
     );

     const hasPlantings = Boolean(sensor.schedules && sensor.schedules.length > 0);
     const latestSchedule = sensor.latest_schedule;
     const isCurrentPlantingHarvested = Boolean(latestSchedule?.actual_harvest_date);
     const isAdmin = auth?.user?.role === 'admin';


     return (
          <>
               <Head title={`Sensor ${sensor.id.substring(0, 8)}`} />
               <div className="p-4 space-y-4">
                    <SensorHeader sensorId={sensor.id} />

                    <SensorCurrentStatus latestReading={sensor.latest_reading} />

                    <SensorQuickStats
                         readingsCount={sensor.readings?.length || 0}
                         schedulesCount={sensor.schedules?.length || 0}
                         createdAt={sensor.created_at}
                         latestReading={sensor.latest_reading}
                    />

                    <SensorCurrentPlanting
                         sensor={sensor}
                         hasPlantings={hasPlantings}
                         latestSchedule={latestSchedule}
                         isCurrentPlantingHarvested={isCurrentPlantingHarvested}
                         isAdmin={isAdmin}
                    />

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

                    <SensorPlantingHistory schedules={sensor.schedules || []} />
               </div>
          </>
     )
};

SensorsShow.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default SensorsShow;
