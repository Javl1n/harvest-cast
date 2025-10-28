import { useSetPanelSize } from '@/hooks/use-set-panel-size';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import sensors from '@/routes/sensors';
import calendar from '@/routes/calendar';
import { SensorInterface, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CircleDot, ChevronLeft, ChevronRight, Calendar as CalendarIcon, Sprout, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
     {
          title: 'Sensor Calendar',
          href: calendar.index().url,
     },
];
const SensorsIndex = () => {
     const {sensors: sensorsData} = usePage<{
          sensors: SensorInterface[];
     }>().props;

     useSetPanelSize(50);

     // Count sensors by status
     const activePlantingsCount = useMemo(() => {
          return sensorsData.filter(sensor => {
               return sensor.latest_schedule && !sensor.latest_schedule.actual_harvest_date;
          }).length;
     }, [sensorsData]);

     const availableSensorsCount = useMemo(() => {
          return sensorsData.filter(sensor => !sensor.latest_schedule).length;
     }, [sensorsData]);

     const harvestedCount = useMemo(() => {
          return sensorsData.filter(sensor => {
               return sensor.latest_schedule && sensor.latest_schedule.actual_harvest_date;
          }).length;
     }, [sensorsData]);

     const formatDate = (date: Date) => {
          return date.toLocaleDateString('en-US', { 
               weekday: 'long', 
               year: 'numeric', 
               month: 'long', 
               day: 'numeric' 
          });
     };

     const formatShortDate = (date: Date) => {
          return date.toLocaleDateString('en-US', { 
               weekday: 'short',
               month: 'short',
               day: 'numeric'
          });
     };

     return (
          <>
               <Head title="Sensor Calendar" />
               <div className='px-6 py-6'>
                    {/* Header with Title */}
                    <div className="flex items-start justify-between mb-6">
                         <div className="flex items-center gap-3">
                              <CalendarIcon className="h-6 w-6" />
                              <div>
                                   <h1 className="text-3xl font-bold">Sensors & Plantings</h1>
                                   <p className="text-muted-foreground mt-1">
                                        Monitor all sensors and their current planting schedules
                                   </p>
                              </div>
                         </div>
                    </div>

                    {/* Stats Overview */}
                    <div className="grid grid-cols-3 gap-4 mb-8">
                         <Card>
                              <CardContent className="pt-6">
                                   <div className="text-2xl font-bold">{activePlantingsCount}</div>
                                   <p className="text-xs text-muted-foreground">Active Plantings</p>
                              </CardContent>
                         </Card>
                         <Card>
                              <CardContent className="pt-6">
                                   <div className="text-2xl font-bold">{availableSensorsCount}</div>
                                   <p className="text-xs text-muted-foreground">Available Sensors</p>
                              </CardContent>
                         </Card>
                         <Card>
                              <CardContent className="pt-6">
                                   <div className="text-2xl font-bold">{harvestedCount}</div>
                                   <p className="text-xs text-muted-foreground">Harvested</p>
                              </CardContent>
                         </Card>
                    </div>

                    {/* Legend */}
                    <div className="flex items-center justify-start gap-6 text-sm mb-8 pb-4 border-b">
                         <div className="flex items-center gap-2">
                              <CircleDot className="h-4 w-4" style={{ color: 'hsl(120, 100%, 50%)' }} />
                              <span className="text-muted-foreground">High Moisture</span>
                         </div>
                         <div className="flex items-center gap-2">
                              <CircleDot className="h-4 w-4" style={{ color: 'hsl(60, 100%, 50%)' }} />
                              <span className="text-muted-foreground">Medium Moisture</span>
                         </div>
                         <div className="flex items-center gap-2">
                              <CircleDot className="h-4 w-4" style={{ color: 'hsl(0, 100%, 50%)' }} />
                              <span className="text-muted-foreground">Low Moisture</span>
                         </div>
                    </div>

                    {/* All Sensors List */}
                    <div>
                         <div className="flex items-center justify-between mb-6">
                              <h2 className="text-2xl font-semibold">All Sensors</h2>
                              <div className="text-sm text-muted-foreground">
                                   {sensorsData.length} sensor{sensorsData.length !== 1 ? 's' : ''}
                              </div>
                         </div>

                         {sensorsData.length > 0 ? (
                              <div className="space-y-6">
                                   {sensorsData.map((sensor) => (
                                        <div key={sensor.id} className="border-b border-border pb-6 last:border-b-0 last:pb-0">
                                             <div className="flex items-start justify-between mb-4">
                                                  <div className="flex items-center gap-3">
                                                       <CircleDot 
                                                            className="h-8 w-8 flex-shrink-0" 
                                                            style={{
                                                                 color: sensor.latest_reading 
                                                                      ? `hsl(${(sensor.latest_reading.moisture / 100) * 120}, 100%, 50%)`
                                                                      : 'hsl(0, 0%, 50%)'
                                                            }} 
                                                       />
                                                       <div>
                                                            <div className="flex items-center gap-2 mb-1">
                                                                 <h3 className="text-2xl font-bold">
                                                                      {sensor.latest_schedule?.commodity?.name || 'No Planting'}
                                                                 </h3>
                                                                 {!sensor.latest_schedule && (
                                                                      <Badge variant="outline" className="text-xs">
                                                                           <Sprout className="h-3 w-3 mr-1" />
                                                                           Available
                                                                      </Badge>
                                                                 )}
                                                                 {sensor.latest_schedule?.actual_harvest_date && (
                                                                      <Badge variant="secondary" className="text-xs">
                                                                           <CheckCircle2 className="h-3 w-3 mr-1" />
                                                                           Harvested
                                                                      </Badge>
                                                                 )}
                                                                 {sensor.latest_schedule && !sensor.latest_schedule.actual_harvest_date && (
                                                                      <Badge variant="default" className="text-xs">
                                                                           Active
                                                                      </Badge>
                                                                 )}
                                                            </div>
                                                            <p className="text-muted-foreground">
                                                                 Sensor ID: {sensor.id.substring(0, 8)}...
                                                            </p>
                                                       </div>
                                                  </div>
                                                  <Link 
                                                       href={calendar.show(sensor)}
                                                       className="text-primary hover:text-primary/80 font-medium"
                                                  >
                                                       View Details
                                                  </Link>
                                             </div>
                                             
                                             {sensor.latest_schedule && (
                                                  <div className="grid grid-cols-4 gap-6 mb-6">
                                                       <div>
                                                            <div className="text-muted-foreground text-sm mb-1">Area</div>
                                                            <div className="font-semibold text-lg">{sensor.latest_schedule.hectares ?? 0} ha</div>
                                                       </div>
                                                       <div>
                                                            <div className="text-muted-foreground text-sm mb-1">Seeds</div>
                                                            <div className="font-semibold text-lg">{sensor.latest_schedule.seeds_planted?.toLocaleString() ?? '0'}</div>
                                                       </div>
                                                       <div>
                                                            <div className="text-muted-foreground text-sm mb-1">Harvest</div>
                                                            <div className="font-semibold text-lg">
                                                                 {sensor.latest_schedule.expected_harvest_date
                                                                      ? new Date(sensor.latest_schedule.expected_harvest_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                                                                      : 'Not set'
                                                                 }
                                                            </div>
                                                       </div>
                                                       <div>
                                                            <div className="text-muted-foreground text-sm mb-1">Income</div>
                                                            <div className="font-semibold text-lg">${sensor.latest_schedule.expected_income?.toLocaleString() ?? '0'}</div>
                                                       </div>
                                                  </div>
                                             )}
                                             
                                             {sensor.latest_reading && (
                                                  <div className="border-t border-border pt-4">
                                                       <div className="text-muted-foreground mb-2">Current Soil Moisture</div>
                                                       <div className="flex items-center justify-between">
                                                            <span className="text-3xl font-bold">{sensor.latest_reading.moisture}%</span>
                                                            <div className="text-sm text-muted-foreground text-right">
                                                                 Last reading:<br/>
                                                                 {new Date(sensor.latest_reading.created_at).toLocaleDateString()} at {new Date(sensor.latest_reading.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                                            </div>
                                                       </div>
                                                  </div>
                                             )}
                                        </div>
                                   ))}
                              </div>
                         ) : (
                              <div className="text-center py-16">
                                   <CalendarIcon className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
                                   <h3 className="text-xl font-medium mb-2">No sensors found</h3>
                                   <p className="text-muted-foreground">
                                        Add sensors to your system to start monitoring plantings.
                                   </p>
                              </div>
                         )}
                    </div>
               </div>
          </>
     );
}

SensorsIndex.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default SensorsIndex;