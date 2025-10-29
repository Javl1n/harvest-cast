import React, { useState } from 'react';
import { SensorInterface } from '@/types';
import { ChevronLeft, ChevronRight, CircleDot, Plus, Calendar as CalendarIcon } from 'lucide-react';
import { Link } from '@inertiajs/react';
import calendar from '@/routes/calendar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DailyCalendarSidebarProps {
    sensors: SensorInterface[];
    onCreateSensor?: (date: Date) => void;
    selectedDate?: Date;
    onDateChange?: (date: Date) => void;
}

const DailyCalendarSidebar: React.FC<DailyCalendarSidebarProps> = ({ 
    sensors, 
    onCreateSensor, 
    selectedDate: propSelectedDate,
    onDateChange 
}) => {
    const [currentDate, setCurrentDate] = useState(propSelectedDate || new Date());

    const handleDateChange = (newDate: Date) => {
        setCurrentDate(newDate);
        if (onDateChange) {
            onDateChange(newDate);
        }
    };

    // Get previous day
    const goToPreviousDay = () => {
        const prevDay = new Date(currentDate);
        prevDay.setDate(currentDate.getDate() - 1);
        handleDateChange(prevDay);
    };

    // Get next day
    const goToNextDay = () => {
        const nextDay = new Date(currentDate);
        nextDay.setDate(currentDate.getDate() + 1);
        handleDateChange(nextDay);
    };

    // Get sensors for the current date
    const getSensorsForDate = (date: Date) => {
        const dateString = date.toISOString().split('T')[0];
        return sensors.filter(sensor => {
            if (!sensor.latest_schedule) return false;
            const plantedDate = new Date(sensor.latest_schedule.date_planted).toISOString().split('T')[0];
            return plantedDate === dateString;
        });
    };

    // Get sensors for the surrounding days (for context)
    const getWeekDays = () => {
        const days = [];
        const startOfWeek = new Date(currentDate);
        startOfWeek.setDate(currentDate.getDate() - 3); // Show 3 days before and after

        for (let i = 0; i < 7; i++) {
            const day = new Date(startOfWeek);
            day.setDate(startOfWeek.getDate() + i);
            days.push({
                date: day,
                isToday: day.toISOString().split('T')[0] === new Date().toISOString().split('T')[0],
                isSelected: day.toISOString().split('T')[0] === currentDate.toISOString().split('T')[0],
                sensors: getSensorsForDate(day)
            });
        }
        return days;
    };

    const todaySensors = getSensorsForDate(currentDate);
    const weekDays = getWeekDays();

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
        <div className="w-64 border-r bg-background flex flex-col min-h-[calc(100vh-4rem)] max-h-[calc(100vh-4rem)]">
            {/* Header */}
            <div className="p-3 border-b">
                <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center gap-1.5">
                        <CalendarIcon className="h-4 w-4" />
                        <h2 className="font-semibold text-sm">Daily Calendar</h2>
                    </div>
                    {onCreateSensor && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-6 w-6 p-0"
                            onClick={() => onCreateSensor(currentDate)}
                        >
                            <Plus className="h-3 w-3" />
                        </Button>
                    )}
                </div>
                
                {/* Date Navigation */}
                <div className="flex items-center justify-between">
                    <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={goToPreviousDay}
                        className="h-6 w-6 p-0"
                    >
                        <ChevronLeft className="h-2.5 w-2.5" />
                    </Button>
                    <div className="text-center px-2">
                        <div className="font-medium text-xs">{currentDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}</div>
                    </div>
                    <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={goToNextDay}
                        className="h-6 w-6 p-0"
                    >
                        <ChevronRight className="h-2.5 w-2.5" />
                    </Button>
                </div>
            </div>

            {/* Week Overview */}
            <div className="p-2 border-b">
                <div className="text-xs font-medium text-muted-foreground mb-1">Week Overview</div>
                <div className="grid grid-cols-7 gap-0.5">
                    {weekDays.map((day, index) => (
                        <button
                            key={index}
                            onClick={() => handleDateChange(day.date)}
                            className={`
                                text-center p-0.5 rounded text-xs transition-colors h-8
                                ${day.isSelected 
                                    ? 'bg-primary text-primary-foreground' 
                                    : day.isToday 
                                    ? 'bg-accent text-accent-foreground' 
                                    : 'hover:bg-muted'
                                }
                            `}
                        >
                            <div className="font-medium text-xs">{day.date.getDate()}</div>
                            <div className="flex justify-center mt-0.5">
                                {day.sensors.length > 0 && (
                                    <div className="flex gap-0.5">
                                        {day.sensors.slice(0, 2).map((sensor, idx) => (
                                            <div
                                                key={idx}
                                                className="w-1 h-1 rounded-full"
                                                style={{
                                                    backgroundColor: sensor.latest_reading 
                                                        ? `hsl(${(sensor.latest_reading.moisture / 100) * 120}, 100%, 50%)`
                                                        : 'hsl(0, 0%, 50%)'
                                                }}
                                            />
                                        ))}
                                        {day.sensors.length > 2 && (
                                            <div className="w-1 h-1 rounded-full bg-muted-foreground/50" />
                                        )}
                                    </div>
                                )}
                            </div>
                        </button>
                    ))}
                </div>
            </div>

            {/* Today's Sensors */}
            <div className="flex-1 overflow-hidden">
                <div className="p-2 border-b">
                    <div className="text-xs font-medium">
                        Plantings on {formatShortDate(currentDate)}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {todaySensors.length} sensor{todaySensors.length !== 1 ? 's' : ''}
                    </div>
                </div>
                
                <div className="flex-1 overflow-y-auto">
                    <div className="p-2 space-y-1">
                        {todaySensors.length > 0 ? (
                            todaySensors.map((sensor) => (
                                <Link
                                    key={sensor.id}
                                    href={calendar.show(sensor)}
                                    className="block"
                                >
                                    <div className="flex items-start gap-2 p-2 rounded bg-card border hover:bg-accent/50 transition-colors">
                                        <CircleDot 
                                            className="h-3 w-3 flex-shrink-0 mt-0.5" 
                                            style={{
                                                color: sensor.latest_reading 
                                                    ? `hsl(${(sensor.latest_reading.moisture / 100) * 120}, 100%, 50%)`
                                                    : 'hsl(0, 0%, 50%)'
                                            }} 
                                        />
                                        <div className="flex-1 min-w-0">
                                            <div className="font-medium text-xs truncate">
                                                {sensor.latest_schedule?.commodity?.name || 'Unknown Crop'}
                                            </div>
                                            <div className="text-xs text-muted-foreground truncate">
                                                {sensor.latest_schedule && (
                                                    <>
                                                        {sensor.latest_schedule.hectares}ha • {sensor.latest_schedule.seed_weight_kg} kg
                                                    </>
                                                )}
                                            </div>
                                            {sensor.latest_reading && (
                                                <div className="text-xs text-muted-foreground">
                                                    {sensor.latest_reading.moisture}% moisture
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            ))
                        ) : (
                            <div className="text-center py-4 px-2 text-muted-foreground">
                                <CalendarIcon className="h-6 w-6 mx-auto mb-1 opacity-50" />
                                <div className="text-xs mb-2">No plantings on this date</div>
                                {onCreateSensor && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-xs h-6"
                                        onClick={() => onCreateSensor(currentDate)}
                                    >
                                        <Plus className="h-2 w-2 mr-1" />
                                        Add Planting
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Legend */}
            <div className="p-2 border-t">
                <div className="text-xs font-medium text-muted-foreground mb-1">Moisture Levels</div>
                <div className="flex items-center gap-2 text-xs">
                    <div className="flex items-center gap-0.5">
                        <CircleDot className="h-2 w-2" style={{ color: 'hsl(120, 100%, 50%)' }} />
                        <span>High</span>
                    </div>
                    <div className="flex items-center gap-0.5">
                        <CircleDot className="h-2 w-2" style={{ color: 'hsl(60, 100%, 50%)' }} />
                        <span>Med</span>
                    </div>
                    <div className="flex items-center gap-0.5">
                        <CircleDot className="h-2 w-2" style={{ color: 'hsl(0, 100%, 50%)' }} />
                        <span>Low</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DailyCalendarSidebar;