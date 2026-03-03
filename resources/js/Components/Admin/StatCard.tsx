import React from 'react';

type TrendDirection = 'up' | 'down' | 'neutral';
type StatCardColor = 'indigo' | 'emerald' | 'amber' | 'rose' | 'sky' | 'violet';

interface StatCardProps {
    title: string;
    value: string | number;
    icon: React.ReactNode;
    trend?: {
        direction: TrendDirection;
        value: string;
        label?: string;
    };
    color?: StatCardColor;
}

const colorMap: Record<StatCardColor, { bg: string; icon: string; trendUp: string; trendDown: string }> = {
    indigo: {
        bg: 'bg-indigo-50',
        icon: 'text-indigo-600 bg-indigo-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
    emerald: {
        bg: 'bg-emerald-50',
        icon: 'text-emerald-600 bg-emerald-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
    amber: {
        bg: 'bg-amber-50',
        icon: 'text-amber-600 bg-amber-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
    rose: {
        bg: 'bg-rose-50',
        icon: 'text-rose-600 bg-rose-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
    sky: {
        bg: 'bg-sky-50',
        icon: 'text-sky-600 bg-sky-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
    violet: {
        bg: 'bg-violet-50',
        icon: 'text-violet-600 bg-violet-100',
        trendUp: 'text-emerald-600',
        trendDown: 'text-rose-600',
    },
};

function TrendIcon({ direction }: { direction: TrendDirection }) {
    if (direction === 'up') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clipRule="evenodd" />
            </svg>
        );
    }
    if (direction === 'down') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clipRule="evenodd" />
            </svg>
        );
    }
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clipRule="evenodd" />
        </svg>
    );
}

export default function StatCard({ title, value, icon, trend, color = 'indigo' }: StatCardProps) {
    const colors = colorMap[color];
    const trendColor =
        trend?.direction === 'up'
            ? colors.trendUp
            : trend?.direction === 'down'
              ? colors.trendDown
              : 'text-gray-500';

    return (
        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-5 flex items-start gap-4 hover:shadow-md transition-shadow duration-200">
            {/* Icon */}
            <div className={['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', colors.icon].join(' ')}>
                {icon}
            </div>

            {/* Content */}
            <div className="min-w-0 flex-1">
                <p className="text-sm text-gray-500 font-medium truncate">{title}</p>
                <p className="mt-0.5 text-2xl font-bold text-gray-900 leading-tight">
                    {value}
                </p>
                {trend && (
                    <div className={['flex items-center gap-1 mt-1.5 text-xs font-medium', trendColor].join(' ')}>
                        <TrendIcon direction={trend.direction} />
                        <span>{trend.value}</span>
                        {trend.label && (
                            <span className="text-gray-400 font-normal">{trend.label}</span>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
