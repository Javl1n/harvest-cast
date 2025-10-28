import React, { useState } from 'react';
import { SensorInterface } from '@/types';
import { ChevronLeft, ChevronRight, CircleDot, Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import calendar from '@/routes/calendar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SensorCalendarProps {
    sensors: SensorInterface[];
    onCreateSensor?: (date: Date) => void;
}

const SensorCalendar: React.FC<SensorCalendarProps> = ({ sensors, onCreateSensor }) => {
    const [currentDate, setCurrentDate] = useState(new Date());

    // Get current month and year
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();

    // Get first day of the month and number of days
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
    const lastDayOfMonth = new Date(currentYear, currentMonth + 1, 0);
    const firstDayWeekday = firstDayOfMonth.getDay();
    const daysInMonth = lastDayOfMonth.getDate();

    // Get previous month's days to fill calendar
    const daysFromPrevMonth = firstDayWeekday;
    const prevMonth = new Date(currentYear, currentMonth - 1, 0);
    const daysInPrevMonth = prevMonth.getDate();

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Navigate months
    const goToPreviousMonth = () => {
        setCurrentDate(new Date(currentYear, currentMonth - 1, 1));
    };

    const goToNextMonth = () => {
        setCurrentDate(new Date(currentYear, currentMonth + 1, 1));
    };

    // Get sensors for a specific date
    const getSensorsForDate = (date: Date) => {
        const dateString = date.toISOString().split('T')[0];
        return sensors.filter(sensor => {
            if (!sensor.latest_schedule) return false;
            const plantedDate = new Date(sensor.latest_schedule.date_planted).toISOString().split('T')[0];
            return plantedDate === dateString;
        });
    };

    // Generate calendar days
    const calendarDays = [];

    // Previous month days
    for (let i = daysFromPrevMonth - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const date = new Date(currentYear, currentMonth - 1, day);
        calendarDays.push({
            date,
            day,
            isCurrentMonth: false,
            isPreviousMonth: true,
            sensors: getSensorsForDate(date)
        });
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        calendarDays.push({
            date,
            day,
            isCurrentMonth: true,
            isPreviousMonth: false,
            sensors: getSensorsForDate(date)
        });
    }

    // Next month days to complete the grid (42 days total = 6 weeks)
    const remainingDays = 42 - calendarDays.length;
    for (let day = 1; day <= remainingDays; day++) {
        const date = new Date(currentYear, currentMonth + 1, day);
        calendarDays.push({
            date,
            day,
            isCurrentMonth: false,
            isPreviousMonth: false,
            sensors: getSensorsForDate(date)
        });
    }

    return (
        <Card className="w-full">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-2xl font-bold">
                        {monthNames[currentMonth]} {currentYear}
                    </CardTitle>
                    <div className="flex gap-2">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={goToPreviousMonth}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={goToNextMonth}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-7 gap-1 mb-2">
                    {dayNames.map(day => (
                        <div key={day} className="p-2 text-center font-semibold text-muted-foreground">
                            {day}
                        </div>
                    ))}
                </div>
                
                <div className="grid grid-cols-7 gap-1">
                    {calendarDays.map((calendarDay, index) => (
                        <div 
                            key={index}
                            className={`
                                min-h-[100px] p-2 border rounded-lg relative
                                ${!calendarDay.isCurrentMonth ? 'bg-muted/30 text-muted-foreground' : 'bg-background'}
                                ${calendarDay.sensors.length > 0 ? 'border-primary/20' : 'border-border'}
                            `}
                        >
                            <div className="flex justify-between items-start mb-2">
                                <span className={`text-sm font-medium ${!calendarDay.isCurrentMonth ? 'text-muted-foreground' : ''}`}>
                                    {calendarDay.day}
                                </span>
                                {calendarDay.isCurrentMonth && onCreateSensor && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="h-6 w-6 p-0"
                                        onClick={() => onCreateSensor(calendarDay.date)}
                                    >
                                        <Plus className="h-3 w-3" />
                                    </Button>
                                )}
                            </div>
                            
                            <div className="space-y-1">
                                {calendarDay.sensors.map((sensor) => (
                                    <Link
                                        key={sensor.id}
                                        href={calendar.show(sensor)}
                                        className="block"
                                    >
                                        <div className="flex items-center gap-1 p-1 rounded text-xs bg-accent/50 hover:bg-accent transition-colors">
                                            <CircleDot 
                                                className="h-3 w-3 flex-shrink-0" 
                                                style={{
                                                    color: sensor.latest_reading 
                                                        ? `hsl(${(sensor.latest_reading.moisture / 100) * 120}, 100%, 50%)`
                                                        : 'hsl(0, 0%, 50%)'
                                                }} 
                                            />
                                            <span className="truncate font-medium">
                                                {sensor.latest_schedule?.commodity?.name || 'Unknown Crop'}
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                
                <div className="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                    <div className="flex items-center gap-2">
                        <CircleDot className="h-4 w-4" style={{ color: 'hsl(120, 100%, 50%)' }} />
                        <span>High Moisture</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <CircleDot className="h-4 w-4" style={{ color: 'hsl(60, 100%, 50%)' }} />
                        <span>Medium Moisture</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <CircleDot className="h-4 w-4" style={{ color: 'hsl(0, 100%, 50%)' }} />
                        <span>Low Moisture</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
};

export default SensorCalendar;