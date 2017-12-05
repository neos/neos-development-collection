define(
	[
		'emberjs',
		'Library/d3/d3'
	],
	function(Ember, d3) {
		/**
		 * A time series chart
		 *
		 * Code taken from the Ember Charts project, 0.3.0 (BSD license)
		 */
		return Ember.View.extend({
			classNames: ['chart-time-series'],

			template: Ember.Handlebars.compile('<svg {{bind-attr width="view.outerWidth" height="view.outerHeight"}}><g class="chart-viewport" {{ bind-attr transform="view.transformViewport" }}></g></svg>'),

			lineData: null,
			ungroupedSeriesName: 'Other',
			interpolate: false,
			marginLeft: 30,
			marginRight: 20,
			marginTop: 10,
			marginBottom: 20,
			defaultOuterHeight: 500,
			defaultOuterWidth: 700,
			outerHeight: Ember.computed.alias('defaultOuterHeight'),
			outerWidth: Ember.computed.alias('defaultOuterWidth'),
			graphicTop: 0,
			graphicLeft: 0,
			graphicWidth: Ember.computed.alias('width'),
			graphicHeight: Ember.computed.alias('height'),
			labelPadding: 10,
			selectedInterval: 'M',
			maxNumberOfLabels: 10,
			minXTicks: 3,
			minYTicks: 3,
			tickSpacing: 50,

			didInsertElement: function() {
				this._super();
				this.set('defaultOuterHeight', this.$().height());
				this.set('defaultOuterWidth', this.$().width());
				Ember.run.once(this, this.draw);
			},

			draw: function() {
				if ((this._state || this.state) !== "inDOM") {
					return;
				}
				if (this.get('hasNoData')) {
					return this.clearChart();
				} else {
					return this.drawChart();
				}
			},

			clearChart: function() {
				return this.$('.chart-viewport').children().remove();
			},

			drawChart: function() {
				this.updateLineData();
				this.updateAxes();
				this.updateLineGraphic();
			},

			updateLineData: function() {
				var series;
				this.removeAllSeries();
				series = this.get('series');
				series.enter().append('g').attr('class', 'series').append('path').attr('class', 'line');
				return series.exit().remove();
			},

			updateAxes: function() {
				var gYAxis, graphicHeight, graphicLeft, graphicTop, xAxis, yAxis;
				xAxis = d3.svg.axis().scale(this.get('xTimeScale')).orient('bottom').tickValues(this.get('labelledTicks')).tickFormat(this.get('formattedTime')).tickSize(-this.get('graphicHeight'));
				yAxis = d3.svg.axis().scale(this.get('yScale')).orient('right').ticks(this.get('numYTicks')).tickSize(this.get('graphicWidth')).tickFormat(this.get('formatValueAxis'));
				graphicTop = this.get('graphicTop');
				graphicHeight = this.get('graphicHeight');
				this.get('xAxis').attr({
					transform: "translate(0," + (graphicTop + graphicHeight + 4) + ")"
				}).call(xAxis);
				graphicLeft = this.get('graphicLeft');
				gYAxis = this.get('yAxis').attr('transform', "translate(" + graphicLeft + ",0)").call(yAxis);
				gYAxis.selectAll('g').filter(function(d) {
					return d;
				}).classed('major', false).classed('minor', true);
				gYAxis.selectAll('text').style('text-anchor', 'end').attr({
					x: -this.get('labelPadding')
				});
			},

			updateLineGraphic: function() {
				var graphicTop, series;
				series = this.get('series');
				graphicTop = this.get('graphicTop');
				series.attr('transform', "translate(0, " + graphicTop + ")");
				return series.select('path.line').attr(this.get('lineAttrs'));
			},

			width: Ember.computed(function() {
				return this.get('outerWidth') - this.get('marginLeft') - this.get('marginRight');
			}).property('outerWidth', 'marginLeft', 'marginRight'),

			height: Ember.computed(function() {
				return Math.max(1, this.get('outerHeight') - this.get('marginBottom') - this.get('marginTop'));
			}).property('outerHeight', 'marginBottom', 'marginTop'),

			$viewport: Ember.computed(function() {
				return this.$('.chart-viewport')[0];
			}),

			viewport: Ember.computed(function() {
				return d3.select(this.get('$viewport'));
			}),

			transformViewport: Ember.computed(function() {
				return "translate(" + (this.get('marginLeft')) + "," + (this.get('marginTop')) + ")";
			}).property('marginLeft', 'marginTop'),

			graphicBottom: Ember.computed(function() {
				return this.get('graphicTop') + this.get('graphicHeight');
			}).property('graphicTop', 'graphicHeight'),

			graphicRight: Ember.computed(function() {
				return this.get('graphicLeft') + this.get('graphicWidth');
			}).property('graphicLeft', 'graphicWidth'),

			hasNoData: Ember.computed(function() {
				return !this.get('_hasLineData');
			}).property('_hasLineData'),

			_hasLineData: Ember.computed.notEmpty('lineData'),

			line: Ember.computed(function() {
				var _this = this;
				return d3.svg.line().x(function(d) {
					return _this.get('xTimeScale')(d.time);
				}).y(function(d) {
					return _this.get('yScale')(d.value);
				}).interpolate(this.get('interpolate') ? 'basis' : 'linear');
			}).property('xTimeScale', 'yScale', 'interpolate'),

			lineColorFn: Ember.computed(function() {
				return function(d, i) {
					return 'rgb(3,181,255)';
				};
			}),

			lineAttrs: Ember.computed(function() {
				var line = this.get('line');
				return {
					"class": function(d, i) {
						return "line series-" + i;
					},
					d: function(d) {
						return line(d.values);
					},
					stroke: this.get('lineColorFn')
				};
			}).property('line'),

			labelledTicks: Ember.computed(function() {
				var count, domain, interval, tick, ticks, _i, _len, _results;
				domain = this.get('xDomain');
				ticks = this.get('tickLabelerFn')(domain[0], domain[1]);
				if (!this.get('centerAxisLabels')) {
					return ticks;
				} else {
					count = 1;
					interval = (function() {
						switch (this.get('selectedInterval')) {
							case 'years':
							case 'Y':
								return 'year';
							case 'quarters':
							case 'Q':
								return 'quarter';
							case 'months':
							case 'M':
								return 'month';
							case 'weeks':
							case 'W':
								return 'week';
							case 'days':
							case 'D':
								return 'day';
							case 'seconds':
							case 'S':
								return 'second';
						}
					}).call(this);
					if (interval === 'quarter') {
						count = 3;
						interval = 'month';
					}
					_results = [];
					for (_i = 0, _len = ticks.length; _i < _len; _i++) {
						tick = ticks[_i];
						_results.push(this._advanceMiddle(tick, interval, count));
					}
					return _results;
				}
			}).property('xDomain'),

			tickLabelerFn: Ember.computed(function() {
				var _this = this;
				switch (this.get('selectedInterval')) {
					case 'years':
					case 'Y':
						return function(start, stop) {
							return _this.labelledYears(start, stop);
						};
					case 'quarters':
					case 'Q':
						return function(start, stop) {
							return _this.labelledQuarters(start, stop);
						};
					case 'months':
					case 'M':
						return function(start, stop) {
							return _this.labelledMonths(start, stop);
						};
					case 'weeks':
					case 'W':
						return function(start, stop) {
							return _this.labelledWeeks(start, stop);
						};
					case 'days':
					case 'D':
						return d3.time.days;
					case 'seconds':
					case 'S':
						return function(start, stop) {
							return _this.labelledSeconds(start, stop);
						};
					default:
						return d3.time.years;
				}
			}).property('maxNumberOfLabels', 'selectedInterval'),

			quarterFormat: function(d) {
				var month, prefix, suffix;
				month = d.getMonth() % 12;
				prefix = "";
				if (month < 3) {
					prefix = 'Q1';
				} else if (month < 6) {
					prefix = 'Q2';
				} else if (month < 9) {
					prefix = 'Q3';
				} else {
					prefix = 'Q4';
				}
				suffix = d3.time.format('%Y')(d);
				return prefix + ' ' + suffix;
			},

			formattedTime: Ember.computed(function() {
				switch (this.get('selectedInterval')) {
					case 'years':
					case 'Y':
						return d3.time.format('%Y');
					case 'quarters':
					case 'Q':
						return this.quarterFormat;
					case 'months':
					case 'M':
						return d3.time.format("%b '%y");
					case 'weeks':
					case 'W':
						return d3.time.format('%-m/%-d/%y');
					case 'days':
					case 'D':
						return d3.time.format('%a');
					case 'seconds':
					case 'S':
						return d3.time.format('%M : %S');
					default:
						return d3.time.format('%Y');
				}
			}).property('selectedInterval'),

			// We support only one series for now
			_groupedLineData: Ember.computed(function() {
				var lineData, _results;
				lineData = this.get('lineData');
				if (Ember.isEmpty(lineData)) {
					return [];
				}
				_results = [{
					group: this.get('ungroupedSeriesName'),
					values: lineData
				}];
				return _results;
			}).property('lineData.@each', 'ungroupedSeriesName'),

			lineSeriesNames: Ember.computed(function() {
				var data;
				data = this.get('_groupedLineData');
				if (Ember.isEmpty(data)) {
					return [];
				}
				return data.map(function(d) {
					return d.group;
				});
			}).property('_groupedLineData'),

			lineDataExtent: Ember.computed(function() {
				var data, extents;
				data = this.get('_groupedLineData');
				if (Ember.isEmpty(data)) {
					return [new Date(), new Date()];
				}
				extents = data.getEach('values').map(function(series) {
					return d3.extent(series.map(function(d) {
						return d.time;
					}));
				});
				return [
					d3.min(extents, function(e) {
						return e[0];
					}), d3.max(extents, function(e) {
						return e[1];
					})
				];
			}).property('_groupedLineData.@each.values'),

			_advanceMiddle: function(time, interval, count) {
				return new Date((time = time.getTime() / 2 + d3.time[interval].offset(time, count) / 2));
			},

			labelledYears: function(start, stop) {
				var skipVal, years;
				years = d3.time.years(start, stop);
				if (years.length > this.get('maxNumberOfLabels')) {
					skipVal = Math.ceil(years.length / this.get('maxNumberOfLabels'));
					return d3.time.years(start, stop, skipVal);
				} else {
					return years;
				}
			},

			labelledQuarters: function(start, stop) {
				var quarters;
				quarters = d3.time.months(start, stop, 3);
				if (quarters.length > this.get('maxNumberOfLabels')) {
					return this.labelledYears(start, stop);
				} else {
					return quarters;
				}
			},

			monthsBetween: function(start, stop, skip) {
				if (skip == null) {
					skip = 1;
				}
				return d3.time.months(start, stop).filter(function(d, i) {
					return !(i % skip);
				});
			},

			labelledMonths: function(start, stop) {
				var months, skipVal;
				months = this.monthsBetween(start, stop);
				if (months.length > this.get('maxNumberOfLabels')) {
					skipVal = Math.ceil(months.length / this.get('maxNumberOfLabels'));
					return this.monthsBetween(start, stop, skipVal);
				} else {
					return months;
				}
			},

			weeksBetween: function(start, stop, skip) {
				if (skip == null) {
					skip = 1;
				}
				return d3.time.weeks(start, stop).filter(function(d, i) {
					return !(i % skip);
				});
			},

			secondsBetween: function(start, stop, skip) {
				if (skip == null) {
					skip = 1;
				}
				return d3.time.seconds(start, stop).filter(function(d, i) {
					return !(i % skip);
				});
			},

			labelledWeeks: function(start, stop) {
				var skipVal, weeks;
				weeks = this.weeksBetween(start, stop);
				if (weeks.length > this.get('maxNumberOfLabels')) {
					skipVal = Math.ceil(weeks.length / this.get('maxNumberOfLabels'));
					return this.weeksBetween(start, stop, skipVal);
				} else {
					return weeks;
				}
			},

			xBetweenSeriesDomain: Ember.computed.alias('lineSeriesNames'),
			xWithinSeriesDomain: Ember.computed.alias('lineDataExtent'),
			xDomain: Ember.computed.alias('xWithinSeriesDomain'),

			yDomain: Ember.computed(function() {
				var lineData, max, maxOfSeries, min, minOfSeries;
				lineData = this.get('_groupedLineData');
				maxOfSeries = d3.max(lineData, function(d) {
					return d3.max(d.values, function(dd) {
						return dd.value;
					});
				});
				minOfSeries = d3.min(lineData, function(d) {
					return d3.min(d.values, function(dd) {
						return dd.value;
					});
				});
				min = minOfSeries;
				max = maxOfSeries;
				if (this.get('yAxisFromZero') || min === max) {
					if (max < 0) {
						return [min, 0];
					}
					if (min > 0) {
						return [0, max];
					}
					if ((min === max && max === 0)) {
						return [-1, 1];
					}
				}
				return [min, max];
			}).property('_groupedLineData', 'yAxisFromZero'),

			yRange: Ember.computed(function() {
				return [this.get('graphicTop') + this.get('graphicHeight'), this.get('graphicTop')];
			}).property('graphicTop', 'graphicHeight'),

			yScale: Ember.computed(function() {
				return d3.scale.linear().domain(this.get('yDomain')).range(this.get('yRange')).nice(this.get('numYTicks'));
			}).property('yDomain', 'yRange', 'numYTicks'),

			xRange: Ember.computed(function() {
				return [this.get('graphicLeft'), this.get('graphicLeft') + this.get('graphicWidth')];
			}).property('graphicLeft', 'graphicWidth'),

			xTimeScale: Ember.computed(function() {
				var xDomain;
				xDomain = this.get('xDomain');
				return d3.time.scale().domain(this.get('xDomain')).range(this.get('xRange'));
			}).property('xDomain', 'xRange'),

			minAxisValue: Ember.computed(function() {
				var yScale;
				yScale = this.get('yScale');
				return yScale.domain()[0];
			}).property('yScale'),

			maxAxisValue: Ember.computed(function() {
				var yScale;
				yScale = this.get('yScale');
				return yScale.domain()[1];
			}).property('yScale'),

			removeAllSeries: function() {
				return this.get('viewport').selectAll('.series').remove();
			},

			series: Ember.computed(function() {
				return this.get('viewport').selectAll('.series').data(this.get('_groupedLineData'));
			}).volatile(),

			xAxis: Ember.computed(function() {
				var xAxis;
				xAxis = this.get('viewport').select('.x.axis');
				if (xAxis.empty()) {
					return this.get('viewport').insert('g', ':first-child').attr('class', 'x axis');
				} else {
					return xAxis;
				}
			}).volatile(),

			yAxis: Ember.computed(function() {
				var yAxis;
				yAxis = this.get('viewport').select('.y.axis');
				if (yAxis.empty()) {
					return this.get('viewport').insert('g', ':first-child').attr('class', 'y axis');
				} else {
					return yAxis;
				}
			}).volatile(),

			numXTicks: Ember.computed(function() {
				var calculatedTicks;
				calculatedTicks = Math.floor(this.get('graphicWidth') / this.get('tickSpacing'));
				return Math.max(calculatedTicks, this.get('minXTicks'));
			}).property('graphicWidth', 'tickSpacing', 'minXTicks'),

			numYTicks: Ember.computed(function() {
				var calculatedTicks;
				calculatedTicks = Math.floor(this.get('graphicHeight') / this.get('tickSpacing'));
				return Math.max(calculatedTicks, this.get('minYTicks'));
			}).property('graphicHeight', 'tickSpacing', 'minYTicks'),

			formatValueAxis: Ember.computed(function() {
				var magnitude, prefix;
				magnitude = Math.max(Math.abs(this.get('minAxisValue')), Math.abs(this.get('maxAxisValue')));
				prefix = d3.formatPrefix(magnitude);
				return function(value) {
					return "" + (prefix.scale(value)) + prefix.symbol;
				};
			}).property('minAxisValue', 'maxAxisValue')
		});
	}
);